<?php

namespace bolt\model\attr;
use \b;

class unique extends \bolt\model\attr\base {

    public function _normalize() {
        $this->_value = ($this->_value ?: uniqid());
        return $this->_value;
    }

}