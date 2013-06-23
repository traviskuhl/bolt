<?php

namespace bolt\model\attr;
use \b;

class model extends \bolt\model\attr\base {
    private $_instance = false;

    public function get($value) {
        if (!$this->_instance) {
            $method = $this->cfg('method', 'findById');
            $args = $this->cfg('args', array());
            if (!$args) {
                $args = array('$'.$this->parent->getPrimaryKey());
            }
            foreach ($args as $i => $arg) {
                if ($arg{0} === '$') {
                    $args[$i] = $this->parent->value(substr($arg,1), false, false);
                }
            }
            $this->_instance = b::model($this->cfg('model'));
            call_user_func_array(array($this->_instance, $method), $args);
        }
        return $this->_instance;
    }

}