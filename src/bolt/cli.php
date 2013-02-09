<?php

namespace bolt;
use \b as b;

// plugin
b::plug('cli', '\bolt\cli');

b::load(array('./bolt/cli/command.php'));

// source
class cli extends plugin {

    // type is singleton
    // since this is really a plugin dispatch
    public static $TYPE = "singleton";

    // hold our commands
    private $_commands = array();

    // construct and include our ventor
    public function __construct() {

        // load the cli
        require bRoot.'/vendor/cli/cli.php';

        // register the autoloader
        \cli\register_autoload();

    }

    public function _default() {
        $args = func_get_args();
        if (count($args) == 0) {
            return $this;
        }
        else {
            return call_user_func_array(array($this, 'addCommand'), $args);
        }
    }


    public function run() {

        // figure out our command
        $cmd = b::command()->match();

        // setup our arguments
        $a = b::cli()->arguments();

        // no command
        if ($cmd === false) {
            return $this->help();
        }

        // create our class
        $o = new $cmd['class']();

        // add flags and they set
        foreach ($cmd['flags'] as $flag) {
            if (is_string($flag[0]) AND stripos($flag[0], '|') !== false) {
                $flag[0] = explode('|', $flag[0]);
            }
            call_user_func_array(array($a, 'addFlag'), $flag);
        }
        foreach ($cmd['options'] as $opt) {
            if (is_string($opt[0]) AND stripos($opt[0], '|') !== false) {
                $opt[0] = explode('|', $opt[0]);
            }
            call_user_func_array(array($a, 'addOption'), $opt);
        }

        // parse
        $a->parse();

        // run
        return $o->run( );


    }

    public function help() {

        var_dump('poop');

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
