<?php

namespace bolt\cli;
use \b;

/// command
b::plug('command', '\bolt\cli\commands');

class commands extends \bolt\plugin\singleton {

    private $_commands = array();

    public function _default() {
        $args = func_get_args();

        if (count($args) > 0) {
            return call_user_func_array(array($this, 'addCommand'), $args);
        }
        return $this;
    }

    public function addCommand($name, $class, $opts=array()) {
        $opts += array(
                'class' => $class,
                'flags' => p_raw('flags', array(), $opts),
                'options' => p_raw('options', array(), $opts),
            );
        $this->_commands[strtolower($name)] = $opts;
        return $this;
    }

    public function match() {
        global $argv;

        // get args
        $script = array_shift($argv);

        // command
        $cmd = $sub = false;

        // loop through and find our first command
        foreach ($argv as $part) {
            $part = strtolower($part);
            if ($part{0} == '-') {continue;}
            if (stripos($part, ':') !== false) {
                list($part, $sub) = explode(':', $part);
            }
            if (array_key_exists($part, $this->_commands)) {
                $cmd = $this->_commands[$part];
                array_shift($argv);
                break;
            }
        }

        // return our full command
        return array($cmd, $sub, $argv);

    }

}


abstract class command  {

    private $_flag;
    private $_option;
    private $_argv = array();

    public function __get($name) {
        $a = b::cli()->arguments();
        $data = $a->getArguments();
        if (array_key_exists($name, $data)) {
            return $data[$name];
        }
        if ($a->getOption($name)) {
            $o = $a->getOption($name);
            return $o['default'];
        }
        if ($a->getFlag($name)) {
            $o = $a->getFlag($name);
            return $o['default'];
        }
        return false;
    }

    public function __call($name, $args) {
        if (method_exists(b::cli(), $name)) {
            return call_user_func_array(array(b::cli(), $name), $args);
        }
        return false;
    }

    public function setArgv($argv) {
        $this->_argv = $argv;
        return $this;
    }
    public function getArgv() {
        return $this->_argv;
    }
    public function err() {
        return call_user_func_array(array(b::cli(), 'err'), func_get_args());
    }

}
