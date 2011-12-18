<?php

namespace bolt;

class view {

    // params
    private $params = array();

    // function
    public function __construct($params=array()) {
        $this->params = $params;
    }

    // param
    public function param($name, $value=false) {
        if ($value) {
            return $this->params[$name] = $value;
        }
        else {
            return $this->param[$name];
        }
    }

    // get
    public function __get($name) {
        return $this->param($name);
    }
    
    // set
    public function __set($name, $value) {
        return $this->param($name, $value);
    }


}