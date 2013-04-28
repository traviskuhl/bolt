<?php

error_reporting(E_ALL);

// do dev mode
define("bDevMode", true);
define("bGlobalSettings", false);
define("bLogLevel", 0);

// include our bolt
include_once(dirname(__FILE__)."/../src/bolt.php");

define("INC", __DIR__."/include");

// do it
class bolt_test extends PHPUnit_Framework_TestCase {

}