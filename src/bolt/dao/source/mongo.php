<?php

namespace bolt\dao\source;
use \b as b;

abstract class mongo extends \bolt\dao\stack {

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
            $this->set($resp);
        }
        
        // this
        return $this;

    }
    
    public function query($query, $args=array()) {
    
        // run our query
        $sth = \b::mongo()->query($this->table, $query, $args);
            
        // loop it up
        foreach ($sth as $item) {
            $this->push(new \bolt\dao\item($this, $item), $item['_id']);
        }
        
        // give me this
        return $this;
    
    }
    
    public function save() {
    
		// edit?
		$edit = $this->getItem()->id;

		// data
		$data = $this->getItem()->normalize();

		// id
		$id = (isset($data['_id']) ? $data['_id'] : $data['id']);

		// unset
		unset($data['id'], $data['_id']);	

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
