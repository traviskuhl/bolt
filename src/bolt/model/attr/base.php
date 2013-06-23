<?php

namespace bolt\model\attr;
use \b;

class base extends \bolt\model\attr {
    private $_cfg;
    private $_name;
    private $_parent;

    final public function __construct($name, $cfg, $parent){
        $this->_name = $name;
        $this->_cfg = $cfg;
        $this->_parent = $parent;
    }

    final public function __get($name) {
        if ($this->{"_$name"}) {
            return $this->{"_$name"};
        }
        return false;
    }

    final public function cfg($name, $default=false) {
        return b::param($name, $default, $this->_cfg);
    }

    final public function call($name, $args) {
        if (in_array($name, array('get', 'set', 'normalize', 'validate'))) {

            // check if there's a callback in config
            if (array_key_exists($this->_cfg, $name) AND is_callable($this->_cfg[$name])) {
                $args[0] = call_user_func($this->_cfg[$name], $args[0], $this->_cfg, $this->_parent);
            }

            // see if the parent class wants to oporate
            if (method_exists($this, $name)) {
                return call_user_func_array(array($this, $name), $args);
            }

            // check if it's a passthrough to use
            else if (method_exists($this, "_$name")) {
                $args[0] = call_user_func_array(array($this, "_{$name}"), $args);
            }

            // return whatever or base class wahts
            return call_user_func_array(array($this, "_base_{$name}"), $args);

        }
        return false;
    }

    // passthrough to call
    public function get($value) { return $this->call('get', func_get_args()); }
    public function set($value) { return $this->call('set', func_get_args()); }
    public function normalize($value) { return $this->call('normalize', func_get_args()); }
    public function validate($value) { return $this->call('validate', func_get_args()); }

    // internal functions
    private function _base_get($value) {
        return $value;
    }

    private function _base_set($value) {
        return $value;
    }

    private function _base_normalize($value) {
        return $value;
    }

    private function _base_validate($value) {
        return $value;
    }

}