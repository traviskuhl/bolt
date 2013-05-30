<?php

namespace bolt\bucket;
use \b;

class bObject implements \bolt\iBucket {

    private $obj = false;


    public function __construct($obj) {
        $this->_obj = $obj;
    }

    public function value() {
        return $this->_obj;
    }

    public function normalize() {
        return $this->_obj;
    }

}