<?php

namespace bolt;
use \b as b;

// plugin
b::plug('cli', '\bolt\cli');

// source
class cli extends plugin {

    // type is singleton
    // since this is really a plugin dispatch
    public static $TYPE = "singleton";

    public function __construct() {

        // load the cli
        require bRoot.'/vendor/cli/cli.php';

        // register the autoloader
        \cli\register_autoload();

    }

    public function line() {
        return call_user_func_array('\cli\line', func_get_args());
    }

    public function err() {
        return call_user_func_array('\cli\err', func_get_args());
    }

    public function out() {
        return call_user_func_array('\cli\out', func_get_args());
    }

    public function dots() {
        return call_user_func_array(array('\cli\Dots', '__construct'), func_get_args());
    }

    public function spinner() {
        return call_user_func_array(array('\cli\Spinner', '__construct'), func_get_args());
    }

    public function progress() {
        return call_user_func_array(array('\cli\progress\Bar', '__construct'), func_get_args());
    }

}