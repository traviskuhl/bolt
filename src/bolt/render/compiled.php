<?php

namespace bolt\render;
use \b;

class compiled {
    private $_data = array();
    private $_file = false;

    public function __construct($file, $data) {
        $this->_file = $file;
        $this->_data = $data;
    }

    public function getFile() {
        return $this->_file;
    }

    public function getData() {
        return $this->_data;
    }

    public static function __set_state($args) {
        return new compiled($args['_file'], $args['_data']);
    }

}