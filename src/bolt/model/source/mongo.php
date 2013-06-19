<?php

namespace bolt\model\source;
use \b as b;

abstract class mongo extends \bolt\model\base {

    // collection
    protected $table = false;

    // enableCaching
    protected $cache = array('enable' => false, 'handler' => 'memcache', 'ttl' => 0);

    public function getCacheHandler() {
        if ($this->cache['handler'] == 'memcache') {
            return b::memcache();
        }
        else {
            return b::cache()->call($this->cache['handler']);
        }
    }

    // get
    public function find() {

        // args
        $args = (func_num_args() > 0 ? func_get_args() : array());


        // morph
        if ($args AND is_string($args[0])) {
            return call_user_func_array(array($this, 'row'), $args);
        }
        else {
            return call_user_func_array(array($this, 'query'), $args);
        }

    }

    public function findById($id) {
        return $this->findOne('id', $id);
    }

    public function findOne() {
        $args = func_get_args();
        $resp = call_user_func_array(array($this, 'find'), $args);
        if (b::isInterfaceOf($resp, '\bolt\model\iResult')) {
            return $resp->item('first');
        }
        else {
            return $this;
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
            unset($resp['_id']);
            $this->set($resp);
        }

        // this
        return $this;

    }

    public function query($query=array(), $args=array()) {

        // get the called class
        $lsb = get_called_class();

        // no fields lets set to get just
        // an id
        // if (!array_key_exists('fields', $args)) {
        //     $args['fields'] = array('_id' => 1);
        // }

        // run our query
        $sth = \b::mongo()->query($this->table, $query, $args);

        $items = array();

        foreach ($sth as $item) {
            unset($item['_id']);
            $items[] = b::model($lsb)->set($item);
        }

        // give me this
        return  \bolt\model\result::create($lsb, $items, 'id');

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
		return $this;

    }

    public function delete() {
        return b::mongo()->delete($this->table, array('_id' => $this->id));
    }

    public function getGridFS() {
        return call_user_func_array(array(b::mongo(), 'getGridFS'), func_get_args());
    }

}
