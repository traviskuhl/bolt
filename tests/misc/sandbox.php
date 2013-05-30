#!/usr/bin/php
<?php

require(__DIR__."/../../src/bolt.php");

error_reporting(E_ALL^E_STRICT);

$_SERVER += array(
        'HTTP_HOST' => 'dev.zuul.backyard.io'
    );

b::init(array(
    'mode' => 'browser'
));

b::request()->initFromEnvironment();