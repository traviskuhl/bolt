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
    private $_handle;

    public function __construct($file) {

        // folder
        if (!file_exists(dirname($file))) {
            $folder = dirname($file);
            mkdir($folder, 0777, true);
        }

        // open our file
        $this->_handle = fopen($file, "w+");

            if (!$this->_handle) {
                return $this;
            }

        // shared lock for reading
        flock($this->_handle, LOCK_SH);
        $content = "";
        while (!feof($this->_handle)) {
            $content .= fread($this->_handle, 8192);
        }
        flock($this->_handle, LOCK_UN);

        // make our bucket
        $this->_bucket = b::bucket($content);

    }

    public function __destruct() {
        if ($this->_handle AND is_resource($this->_handle)) {
            flock($this->_handle, LOCK_EX);
            fwrite($this->_handle, $this->_bucket->asJson());
            flock($this->_handle, LOCK_UN);
            fclose($this->_handle);
        }
    }

    // __get
    public function __get($name) {
        return $this->get($name);
    }

    // __set
    public function __set($name, $value) {
        return $this->set($name, $value);
    }

    public function __call($name, $args) {
        return call_user_func_array(array($this->_bucket, $name), $args);
    }


}