<?php

error_reporting(E_ALL);

// do dev mode
define("bDevMode", true);
define("bGlobalSettings", false);
define("bLogLevel", 0);

// get our depend file from build
$depend = json_decode(file_get_contents("../build/depend.json"), true);

$_SERVER['HTTP_HOST'] = 'test.bolthq.com';

// include our bolt
include_once(dirname(__FILE__)."/../src/bolt.php");

// add depend
foreach ($depend as $pkg) {
    $dir = __DIR__."/../vendor/{$pkg['name']}/{$pkg['lib']}/";
    if (file_exists($dir)) {
        b::$autoload[] = $dir;
    }
}

// short
define("INC", __DIR__."/include");

// do it
class bolt_test extends PHPUnit_Framework_TestCase {

}