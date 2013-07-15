<?php

namespace bolt\model\attr;
use \b;

class bool extends \bolt\model\attr\base {
    private $_bucket = false;

    protected function init() {
        $this->_bucket = new \bolt\bucket\bString(false, false, false);
    }

    public function get() {
        return $this->_bucket;
    }

    public function set($value) {
        $this->_bucket->set((bool)$value);
    }

    public function normalize() {
        return (bool)$this->_bucket->value;
    }

    public function value() {
        return $this->normalize();
    }

}