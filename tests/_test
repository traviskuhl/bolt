#!/usr/bin/php
<?php

// bolt
include("../src/bolt.php");

// init bolt
b::init(array(
    'config' => array(
        'autoload' => array(
            realpath(dirname(__FILE__)."/../framework/src/")
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

// session
 $s = b::dao('\bolt\common\dao\sessions')->get('id', '67bf2206-5b45-11e1-a224-d38cf5eefb30');

//$s = b::dao('\bolt\common\dao\sessions')->get(array());

/*

// create
$s->set(array(
    'expires' => 'hello'
));

// save
$s->save();
*/

var_dump($s->data_deep);

?>