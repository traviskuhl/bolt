<?php

namespace bolt\model\attr;
use \b;

class attr_array extends \bolt\model\attr\base {
    private $_bucket = false;

    const NAME = 'array';

    protected function init() {
        $this->_bucket = new \bolt\bucket\bArray(array(), false, false);
    }

    public function get() {
        return $this->_bucket;
    }

    public function set($value) {
        if (is_array($value) AND $value) {
            $this->_bucket->set($value);
        }
    }

    public function normalize() {
        return $this->_bucket->asArray();
    }

    public function value() {
        return $this->normalize();
    }

}