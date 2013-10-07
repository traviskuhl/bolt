<?php

// bolt namespace
namespace bolt;
use \b;

// plugin to b
b::plug('settings', "\bolt\settings");

interface iSettings {
    public function get();
}

// our settings is singleton
class settings extends plugin\singleton {

    public static $prefetch = array();

    public function load($path, $root=false) {
        $settings = b::package()->getDirectories("settings");
        $settings[] = b::package()->getRoot();
        if ($root) { $settings[] = $root;}
        if (is_array($settings)) {


          foreach ($settings as $dir) {
                $file = b::path($dir, $path);
                if (file_exists($file)) {
                    return new settings\json($file);
                }
            }
        }
        return false;
    }

    public function importFile($file, $root) {
        if (substr($file,0,7) == 'file://') {
            $file = substr($file,7);
        }

        $f = $this->load($file, $root);

        $this->_default($f->get());

        return $this;

    }

     // data
     private $_bucket;

     public function __construct() {
         $this->_bucket = b::bucket(array());
     }

     // default
     public function _default($data=false) {
         if (is_array($data)) {
             $this->_bucket->set($data);
         }
         else if (is_string($data)) {
             return $this->_bucket->get($data);
         }
         return $this;
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

     public function __isset($name) {
        return $this->_bucket->exists($name);
     }

     public function asArray() {
         return $this->_bucket->asArray();
     }

     public function set($name, $value) {
        if (is_string($value) AND substr($value,0,7) == 'file://') {
            $file = substr($value,7);


            $value = $this->load($file);

        }

        if (b::isInterfaceOf($value, '\bolt\iSettings')) {
            $value = $value->get();
        }

        $env = b::env();

        if (is_array($value) AND isset($value["_{$env}"])) {
            $value = b::mergeArray($value, $value["_{$env}"]);
            unset($value["_{$env}"]);
        }

        return call_user_func(array($this->_bucket, 'set'), $name, $value);
     }


}
