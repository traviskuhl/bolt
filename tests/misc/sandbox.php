#!/usr/bin/php
<?php

require(__DIR__."/../../src/bolt.php");

error_reporting(E_ALL^E_STRICT);

define("bSelf", "http://localhost?pooper=1");


b::init(array(

    ));


$b = b::bucket(array(
        'a' => 'a',
        'b' => 'b',
        'c' => 'c'
    ));

var_dump($b->asArray()); die;