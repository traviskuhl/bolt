<?php

// some bolt flags
define("bMode", "dev");
define("bLogLevel", 1);
define("bRoot", __DIR__."/../../src");

// require bolt from the
require(realpath(bRoot."/bolt.php"));

// define our own root
$root = __DIR__;

// init bolt
b::init(array(
    'mode' => 'browser',
    'autoload' => array(
        $root
    ),
   'load' => array(
        "{$root}/controllers/*.php",
        "{$root}/views/*.php",
    ),
   'config' => array(
        "templates" => "{$root}/templates"
    )
));

// run in browser
b::run();