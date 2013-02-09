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
        $this->_commands[strtolower($name)] = array(
                'class' => $class,
                'flags' => p_raw('flags', array(), $opts),
                'options' => p_raw('options', array(), $opts)
            );
        return $this;
    }

    public function match() {
        global $argv;

        // get args
        $script = array_shift($argv);

        // command
        $cmd = false;

        // loop through and find our first command
        foreach ($argv as $part) {
            $part = strtolower($part);
            if ($part{0} != '-' AND array_key_exists($part, $this->_commands)) {
                $cmd = $this->_commands[$part]; break;
            }
        }

        // return our full command
        return $cmd;

    }

}


abstract class command  {

    private $_flag;
    private $_option;

    abstract public function run();


    public function __get($name) {
        $a = b::cli('arguments');
        $data = $a->getArguments();
        if (array_key_exists($name, $data)) {
            return $data[$name];
        }
        if ($a->getOption($name)) {
            $o = $a->getOption($name);
            return $o['default'];
        }
        return false;
    }

    public function __call($name, $args) {
        if (method_exists(array(b::cli(), $name))) {
            return call_user_func_array(array(b::cli(), $name), $args);
        }
        return false;
    }

}
