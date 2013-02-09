<?php

namespace bolt\dao;
use \b;

class result extends \bolt\bucket {

    // private
    private $_guid; /// guid for unique objects
    private $_meta;

    public function __construct($items, $key='id') {
        foreach ($items as $item) {

        }
    }

    public function __call($name, $args) {
        if ($this->_meta) {
            return call_user_func_array(array($this->_meta, $name), $args);
        }
        return false;
    }


}


