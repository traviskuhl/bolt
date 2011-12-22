#!/usr/bin/php
<?php

// bolt
include("./framework/src/bolt.php");

// init bolt
b::init(array(
    'config' => array(
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
        ),
        'session' => array(
            'cookie' => 's',
            'exp' => '+2 weeks'
        ),
        'defaultView' => "testRoute"
    )
));

// config
b::config();

// route
b::route("test/poop/([a-z]):test/([0-9]):id", '\testRoute', "test");

class testRoute extends \bolt\view  {
    function template($tmpl) {
        return dirname(__FILE__)."/$tmpl";
    }
    function get() {
        $this->render("page.template.php",array(
            'hello' => 'world'
        ));
    }
    function post() {
        $this->render(array('name' => 'world'))->string('hello {$name}');
    }
    function ajax() {
        $this->setData(array(
            'hello' => 'world'
        ));
    }
}


define("bPath","test/poop/a/0");


b::run(array(
    'accept' => 'text/javascript;text/xhr'
));

var_dump( b::url('test', array('test'=>'aba', 'id' => 1)) );


var_dump(b::md5('x'));

// dao
$test = b::dao('bolt\common\dao\test')->get(array());

// load
var_dump("loaded -- ".$test->loaded());

?>