#!/usr/bin/php
<?php

// bolt
include("../../src/bolt.php");

// init bolt
b::init(array(
    'config' => array(
        'autoload' => array(
            realpath(dirname(__FILE__)."/../framework/src/")
        ),
    )
));

b::run();


