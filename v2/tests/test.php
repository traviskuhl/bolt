#!/usr/bin/php
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

var_dump(b::md5('x'));

// dao
$test = b::dao('bolt\common\dao\test')->get(array());

// load
var_dump("loaded -- ".$test->loaded());

// route
b::route("test/poop/([a-z]):test/([0-9]):id", '\testRoute');

class testRoute extends \bolt\view  {
    function __construct() {
        var_dump(func_get_args() );
    }
    function get() {
    
    
    }
}


b::route()->match("test/poop/a/0");

?>