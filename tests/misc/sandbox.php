#!/usr/bin/php
<?php

require(__DIR__."/../../src/bolt.php");

error_reporting(E_ALL^E_STRICT);

define("bSelf", "http://localhost?pooper=1");

b::init(array(
    ));

b::depend('bolt-browser-*');

b::route('test/{test}', function(){


})->name('test');


var_dump( b::url("http://poop.com/", array('test' => '1')) );

var_dump( b::url("test", array('test' => '1'), array('poop'=>'a'), array('use-base-query'=>1)) );
