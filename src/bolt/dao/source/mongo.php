<?php

namespace bolt\dao\source;
use \b as b;

abstract class mongo extends \bolt\dao\item {

    // collection
    protected $table = false;

    // enableCaching
    protected $cache = array('enable' => true, 'handler' => 'memcache', 'ttl' => 0);

    public function getCacheHandler() {
        if ($this->cache['handler'] == 'memcache') {
            return b::memcache();
        }
        else {
            return b::cache()->call($this->cache['handler']);
        }
    }

    // get
    public function get() {

        // args
        $args = func_get_args();

        // morph
        if (is_array(func_get_arg(0))) {
            return call_user_func_array(array($this, 'query'), $args);
        }
        else {
            return call_user_func_array(array($this, 'row'), $args);
        }

    }

    public function count($query, $args=array()) {
        return b::mongo()->count($this->table, $query, $args);
    }

    public function row($field, $val=array()) {

        // start off with no resp
        $resp = false;

        // if field is id and cache is enabled
        // we should check the cache
        if ($this->cache['enable'] == true AND $field == 'id' AND $val) {
            $cid = "{$this->table}:{$val}";
            $resp = call_user_func(array($this->getCacheHandler(), 'get'), $cid);
        }

        // no resp
        if (!$resp) {

            // query that shit up
            $resp = \b::mongo()->row($this->table, array($field => $val));

            // if field is id and cache is enabled
            // we should check the cache
            if ($this->cache['enable'] == true AND $field == 'id' AND $resp) {
                $cid = "{$this->table}:{$val}";
                $resp['id'] = $val;
                call_user_func(array($this->getCacheHandler(), 'set'), $cid, $resp, $this->cache['ttl']);
            }

        }

        // what up
        if ($resp) {
            $this->setData($resp);
        }

        // this
        return $this;

    }

    public function query($query, $args=array()) {

        // get the called class
        $lsb = get_called_class();

        // no fields lets set to get just
        // an id
        if (!array_key_exists('fields', $args)) {
            $args['fields'] = array('_id' => 1);
        }

        // run our query
        $sth = \b::mongo()->query($this->table, $query, $args);

        // stack
        $stack = new \bolt\dao\result($lsb);

        // loop it up
        foreach ($sth as $item) {
            $stack->push(b::dao($lsb)->get('id', $item['_id']), $item['_id']);
        }

        // give me this
        return $stack;

    }

    public function save($data=false) {

        // set data
        if ($data AND is_array($data)) {
            $this->set($data);
        }

		// edit?
		$edit = $this->id;

		// data
		$data = $this->normalize();

		// id
		$id = (isset($data['_id']) ? $data['_id'] : $data['id']);

		// unset
		unset($data['id'], $data['_id']);

        if ($id === false) {
            $id = uniqid();
        }

		// save it
		try {
			$r = \b::mongo()->update($this->table, array('_id' => $id), array('$set' => $data), array('upsert'=>true, 'safe'=>true));
		}
		catch (MongoCursorException $e) {
			return false;
		}

        // yes we should update the cache
        if ($this->cache['enable'] == true AND $r) {
            $cid = "{$this->table}:{$id}";
            $data['id'] = $id;
            call_user_func(array($this->getCacheHandler(), 'set'), $cid, $data, $this->cache['ttl']);
        }

		// save id
		$this->id = $id;

		// give back
		return $id;

    }

    public function delete() {
        return b::mongo()->delete($this->table, array('_id' => $this->id));
    }

    public function getGridFS() {
        return call_user_func_array(array(b::mongo(), 'getGridFS'), func_get_args());
    }

}
