<?php

// bolt
include("./framework/src/bolt.php");

// init bolt
b::init();

// config
b::config(array(
    'autoload' => array(
        function($class) { 
            $class = str_replace("bolt/common/dao/", "common/dao/src/", $class);
            $path = realpath(dirname(__FILE__)."/../{$class}.php");            
            if (file_exists($path)) { return include_once($path); }
        },
    ),
    'mongo' => array(
        'host' => "72.14.185.109",
        'port' => 27017,
        'db' => "test"
    )
));

// route
b::route("test", '\test');

class test extends \bolt\view {

    public function get() {
    
        $args = array();
    
        return $this->render(
            "template.php",
            $args
        );    
    
    }

    public function post() {
    
        return $this->render()->string("hello world");
    
    }

}
