<?php

namespace bolt\dao\source;

abstract class mongo extends \bolt\dao\stack {

    // collection
    protected $table = false;

    // get
    public function get() {
    
        // args
        $args = func_get_args();
        
        // morph
        if (is_array(func_get_arg(0))) {
            call_user_func_array(array($this, 'row'), $args);
        }
        else {
            call_user_func_array(array($this, 'query'), $args);        
        }        
    }
    
    public function row($field, $val=array()) {
    
        // query that shit up
        return $this->query(array($field => $val))->item('first');
    
    }
    
    public function query($query, $args=array()) {
    
        // run our query
        $sth = b::mongo()->query($this->table, $query, $args);
    
        // loop it up
        foreach ($sth as $item) {
            $this->push(new \bolt\dao\item($this->getStruct(), $item), $item['_id']);
        }
        
        // give me this
        return $this;
    
    }

}
