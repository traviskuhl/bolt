#!/usr/bin/env php
<?php


// do dev mode
define("bDevMode", true);
define("bGlobalSettings", false);
define("bLogLevel", 0);

// include our bolt
include_once(dirname(__FILE__)."/../../src/bolt.php");

// we need to int part of bolt
b::init(array(
    'config' => array(
        'autoload' => array()
    ),
    'core' => array('bucket','render')
));

// data array
$data = array(
    'string' => 'this is a string value',
    'no key',
    'array' => array(
        'no key',
        'key1' => 'key value 1',
        'key2' => 'key value 2',
        'nested' => array(
            'key1' => 'nest key value 1',
            'key2' => "nest by value 2",
            'nest no key'
        )
    ),
    'bool' => true,
    'int' => 1,
    'float' => 1.1,
);


class item extends \bolt\dao\item {

    public function getStruct() {
        return array(
            'id' => array(),
            'media' => array()
        );
    }


}

$b = b::bucket($data);

$b->n1->push('ns', 'a');



var_dump($b->asArray()); die;

