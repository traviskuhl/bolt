<?php

namespace bolt\model\attr;
use \b;

class model extends \bolt\model\attr\base {
    private $_key = false;
    private $_instance = false;
    private $_loaded = false;

    private function _inst() {
        if (!$this->_instance) {
            $method = $this->cfg('method', 'findById');
            $args = $this->cfg('args', array('$'.$this->name));
            $model = $this->cfg('model');

            if (!$model) {return;}

            foreach ($args as $i => $arg) {
                if ($arg{0} === '$') {
                    $args[$i] = $this->parent->value(substr($arg,1), false, false);
                }
            }
            $this->_instance = b::model($model);
            if ($method == 'set') {
                $args = $this->parent->asArray();
            }
            call_user_func_array(array($this->_instance, $method), $args);
            $this->_loaded = true;
        }
        return $this->_instance;
    }

    public function get() {
        return $this->_inst();
    }

    public function set($value) {
        if (is_array($value)) {
            $this->_inst()->set($value);
        }
        else {
            $this->_key = (string)$value;
        }
    }

    public function normalize() {
        return $this->_key;
    }

    public function value() {
        return $this->normalize();
    }

}