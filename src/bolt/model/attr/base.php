<?php

namespace bolt\model\attr;
use \b;

class base extends \bolt\model\attr {
    private $_cfg;
    private $_name;
    private $_parent;
    protected $_value = false;

    final public function __construct($name, $cfg, $parent){
        $this->_name = $name;
        $this->_cfg = $cfg;
        $this->_parent = $parent;
        $this->init();
    }

    protected function init() {}

    final public function __get($name) {
        if ($name == 'value') {
            return $this->value();
        }
        else if ($this->{"_$name"}) {
            return $this->{"_$name"};
        }
        return false;
    }

    final public function __set($name, $value) {
        if ($name == 'value') {
            $this->set($value);
        }
        return false;
    }

    final public function cfg($name, $default=false) {
        return b::param($name, $default, $this->_cfg);
    }

    final public function call($name, $args=array()) {
        if (in_array($name, array('get', 'set', 'normalize', 'validate'))) {


            // check if there's a callback in config
            if (array_key_exists($name, $this->_cfg) AND is_callable($this->_cfg[$name])) {
                $args[0] = call_user_func($this->_cfg[$name], $args[0], $this->_cfg, $this->_parent);
            }

            // return whatever or base class wahts
            return call_user_func_array(array($this, $name), $args);

        }
        else if (method_exists($this, $name)) {
            return call_user_func_array(array($this, $name), $args);
        }
        return false;
    }

    // internal functions
    public function value() {
        return $this->_value;
    }

    public function get() {
        return $this->_value;
    }

    public function set($value) {
        $this->_value = $value;
        return $value;
    }

    public function normalize() {
        if (!$this->_value AND $this->cfg('default')) {
            $this->_value = $this->cfg('default');
        }
        return $this->_value;
    }

    public function validate($value) {
        return true;
    }

}