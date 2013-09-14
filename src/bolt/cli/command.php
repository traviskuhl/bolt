<?php

namespace bolt\cli;
use \b;

abstract class command  {
    private $_options = array();

    // some defaults
    public static $options = array();
    public static $commands = array();

    // options
    public function setOptions($opts) {
        $this->_options = $opts;
        return $this;
    }

    public function __get($name) {
        if (array_key_exists($name, $this->_options)) {
            return $this->_options[$name];
        }
        return false;
    }

    public function __call($name, $args) {
        if (method_exists(b::cli(), $name)) {
            return call_user_func_array(array(b::cli(), $name), $args);
        }
    }


    public function exec($cmd, $su = false) {

        // tmp
        $tmp = $this->tmp() . uniqid();

        if ($su) {
            $cmd = "sudo -u {$su} {$cmd}";
        }

        // run it
        system("$cmd &>$tmp");

        $lines = explode("\n", trim(file_get_contents($tmp)));

        // no tmp
        unlink($tmp);

        // verbose
        $this->verbose("exec: $cmd", $lines);

        // give back
        return $lines;

    }

}
