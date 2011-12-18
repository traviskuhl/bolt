#!/usr/bin/php
<?php

// bolt
include("../framework/src/bolt.php");

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
    )
));

// dao
$test = b::dao('bolt\common\dao\test')->get('id');

var_dump('x', $test); die;

?>