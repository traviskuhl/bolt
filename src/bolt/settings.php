<?php

// bolt namespace
namespace bolt;
use \b;

// plugin to b
b::plug('settings', "\bolt\settings");

// our settings is singleton
class settings extends plugin\factory {

    // data
    private $_bucket;
    private $_file;

    public function __construct($file) {
        $this->_bucket = b::bucket();

        // must be a string
        if (!is_string($file)) {return;}

        // not readable
        if (!is_readable($file)) {
            b::log("Settings location '%s' is not writeable", array($file), b::LogFatal);
            return false;
        }

        // folder
        if (!file_exists(dirname($file))) {
            $folder = dirname($file);
            mkdir($folder, 0777, true);
        }

        // data
        $data = json_decode(file_get_contents($file), true);

        // env
        $env = "_".b::env();

        if (isset($data[$env])) {
            $data = b::mergeArray($data, $data[$env]);
            unset($data[$env]);
        }

        // make our bucket
        $this->_bucket->set($data);

        // save file
        $this->_file = $file;

    }


    // __get
    public function __get($name) {
        return $this->_bucket->get($name);
    }

    // __set
    public function __set($name, $value) {
        return $this->_bucket->set($name, $value);
    }

    public function __call($name, $args) {
        return call_user_func_array(array($this->_bucket, $name), $args);
    }


}