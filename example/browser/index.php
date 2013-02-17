<?php

// some bolt flags
define("bMode", "dev");
define("bLogLevel", 1);

// require bolt from the
require("bolt.php");

// define our own root
$root = __DIR__;

// init bolt
b::init(array(
    'autoload' => array(
        $root
    ),
   'load' => array(
        "{$root}/controllers/*.php",
    ),
   'config' => array(

    )
));

// run in browser
b::run('browser');