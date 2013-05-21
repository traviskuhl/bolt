<?php

namespace bolt\cli;
use \b;

abstract class command  {
    private $_options = array();

    // some defaults
    public static $options = array();
    public static $commands = array();

    // options
    public function setOptions($opts) {
        $this->_options = $opts;
        return $this;
    }

    public function __get($name) {
        if (array_key_exists($name, $this->_options)) {
            return $this->_options[$name];
        }
        return false;
    }


}
