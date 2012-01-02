<?php

namespace bolt\dao\source;
use \b as b;

abstract class mongo extends \bolt\dao\stack {

    // collection
    protected $table = false;

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
    
        // query that shit up
        $resp = \b::mongo()->row($this->table, array($field => $val));
                        
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
