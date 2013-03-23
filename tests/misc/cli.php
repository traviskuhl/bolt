#!/usr/bin/php
<?php

// bolt
include("../../src/bolt.php");

// if we're in cli, load it
// otherwise we're just including the phar
if ('cli' === php_sapi_name()) {

    // init our bolt instance
    b::init(array(
        'mode' => 'cli',
        'load' => array(
            bRoot."/bolt/client/*.php"
        )
    ));

    // get our settings
    $home = ".";
    $user = p('SUDO_USER', p('USER', false, $_SERVER), $_SERVER);

    // home
    if ($home === false) {
        $home = "/home/{$user}";
    }
    if (!file_exists("{$home}/.bolt")) {
        mkdir("{$home}/.bolt/");
    }

    // settings
    b::setSettings('client', "{$home}/.bolt/settings.json");

    // when we're done, save settings
    b::on('destruct', function(){
        b::settings('client')->save();
    });

    // run
    b::run();

}
else {
    exit("Unable to run!");
}