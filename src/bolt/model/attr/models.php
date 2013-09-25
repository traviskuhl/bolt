<?php

namespace bolt\model\attr;
use \b;

class models extends \bolt\model\attr\base {
    private $_key = false;
    private $_result = false;


    public function get() {
        return $this->_result;
    }

    public function set($value) {
        $model = $this->cfg['model'];
        $this->_result = new \bolt\model\result($model, $value);
    }

    public function normalize() {
        return $this->_key;
    }

    public function value() {
        return $this->normalize();
    }

}