#!/usr/bin/php
<?php

// bolt
include("../framework/src/bolt.php");

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
$s = b::dao('\bolt\common\dao\sessions')->get('id', 'b6aff2fc-32fd-11e1-8345-7379ae0f4ae6');

// create
$s->set(array(
    'expires' => 'hello'
));

// save
$s->save();

var_dump($s->id);

?>