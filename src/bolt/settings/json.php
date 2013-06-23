<?php

namespace bolt\settings;
use \b;

class json implements \bolt\iSettings {
    private $_file;
    public function __construct($file) {
        $this->_file = $file;
    }
    public function get() {
        return json_decode(file_get_contents($this->_file), true);
    }

    public static function __set_state($array) {
        return new json($array['_file']);
    }

}