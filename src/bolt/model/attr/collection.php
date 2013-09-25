<?php

namespace bolt\model\attr;
use \b;

class collection extends \bolt\model\attr\base {
    private $_key = false;
    private $_item = false;
    private $_children = array();


    public function get() {
        return $this->_children;
    }

    public function set($value) {


        $config = $this->getConfig();
        $config['type'] = $config['of'];
        unset($config['of']);

        foreach ($value as $i => $item) {
            $this->_children[$i] = $this->getParent()->createAttribute($config['type'], $i, $config);

            var_dump($this->_children[$i]); die;
        }

    }

    public function normalize() {
        $items = array();
        foreach ($this->_children as $i => $item) {
            $items[$i] = $item->normalize();
        }
        return $items;
    }

    public function value() {
        return $this->normalize();
    }

}