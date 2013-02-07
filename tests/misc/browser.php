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
        'mongo' => array(
            'host' => "72.14.185.109",
            'port' => 27017,
            'db' => "test"
        ),
        'session' => array(
            'cookie' => 's',
            'exp' => '+2 weeks'
        ),
    )
));

// // route
// b::route("(?P<say>[^/]+)/(?P<place>[^/]+)", function($place, $say){
//     return "poop";
// });

b::route(
        b::route('regex', '^(hello)>say/(world)>place/?$', 'GET'),
        function($say, $place) {
            return 'fuck';

        }
    );



b::run(array(
    'path' => "hello/world",
));



?>