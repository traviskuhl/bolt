<?php

namespace bolt\model;
use \b;

class result implements \bolt\iBucket, \ArrayAccess, \Iterator, \Countable {

    // private
    private $_guid; /// guid for unique objects
    private $_class = false;
    private $_loaded = false;
    private $_meta;
    private $_total = 0;
    private $_limit = 0;
    private $_offset = 0;
    private $_items = array();
    private $_struct = false;

    public static function create($class, $items=array()) {
        return new result($class, $items, $key);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief construct a result
    ///
    /// @return void
    ////////////////////////////////////////////////////////////////////
    public function __construct($class=false, $items=false, $key='id', $struct=false) {
        $this->_class = $class;
        $this->_struct = $struct;
        if (is_array($items)) {
            $this->setItems($items, $key);
        }
    }

    public function setClass($class) {
        $this->_class = $class;
        return $this;
    }
    public function setStruct($struct) {
        $this->_struct = $struct;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC passthrough call to meta
    ///
    /// @param $name name of method
    /// @param $args array of arguments
    /// @return result of call
    ////////////////////////////////////////////////////////////////////
    public function __call($name, $args) {
        if ($this->_meta) {
            return call_user_func_array(array($this->_meta, $name), $args);
        }
        else if (method_exists($this, $name)) {
            return call_user_func_array(array($this, $name), $args);
        }
        return false;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC passthrough to meta
    ///
    /// @param $name name of variable
    /// @return variable
    ////////////////////////////////////////////////////////////////////
    public function __get($name) {
        if ($name == 'value') {
            return $this->_items;
        }
        else if ($this->_meta) {
            return call_user_func(array($this->_meta, '__get'), $name);
        }
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC passthrough to meta
    ///
    /// @param $name name of variable
    /// @param $value value of variable
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function __set($name, $value) {
        return call_user_func(array($this->_meta, '__set'), $name, $value);
    }

    public function __isset($name) {
        return array_key_exists($name, $this->_items);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief has this item been loaded
    ///
    /// @return bool if item is loaded
    ////////////////////////////////////////////////////////////////////
    public function loaded() {
        return $this->_loaded;
    }

    public function asArray() {
        $resp = array();
        foreach ($this->_items as $item) {
            $resp[] = $item->asArray();
        }
        return $resp;
    }

    public function item($index) {
        switch($index) {
            case 'first':
                $keys = array_keys($this->_items);
                $index = array_shift($keys); break;
        };

        if (array_key_exists($index, $this->_items)) {
            return $this->_items[$index];
        }
        else {
            return b::dao($this->_class);
        }
    }

    public function push($item, $key='id') {
        if (!is_a($item, $this->_class)) {
            $item = b::model($this->_class)->setStruct($this->_struct)->set($item);
        }
        $id = $item->value($key);
        $this->_items[$id] = $item;
    }

    public function map($cb, $useKeys=true) {
        $b = array();
        foreach ($this->_items as $id => $item) {
            if (is_string($cb) AND method_exists($item, $cb)) {
                $cb = array($item, $cb);
            }
            if (is_callable($cb)) {
                $b[$id] = call_user_func($cb, $item);;
            }
            else {
                $b[$id] = $item;
            }
        }
        return b::a($useKeys===true ? $b : array_values($b));
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set the items
    ///
    /// @param $items array of item objects
    /// @param $key name of item key
    /// @param $class class name
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setItems($items, $key='id') {
        foreach ($items as $name => $item) {
            if ($key === false) {
                if (!is_a($item, $this->_class)) {
                    $item = b::model($this->_class)->setStruct($this->_struct)->set($item);
                }
                $this->_items[$name] = $item;
            }
            else {
                if (!is_a($item, $this->_class)) {
                    $item = b::model($this->_class)->setStruct($this->_struct)->set($item);
                }
                $this->_items[(string)$item->value($key)] = $item;
            }
        }
        $this->_loaded = true;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set the meta item
    ///
    /// @param $meta meta item object
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setMeta(\bolt\dao\item $meta) {
        $this->_meta = $meta;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get a meta item
    ///
    /// @return meta item
    ////////////////////////////////////////////////////////////////////
    public function getMeta() {
        return $this->_meta;
    }

    public function setTotal($total) {
        $this->_total = $total;
        return $this;
    }
    public function getTotal() {
        return $this->_total;
    }

    public function setLimit($limit) {
        $this->_limit = $limit;
        return $this;
    }
    public function getLimit(){
        return $this->_limit;
    }

    public function setOffset($offset) {
        $this->_offset = $offset;
        return $this;
    }
    public function getOffset() {
        return $this->_offset;
    }

    public function getPages() {
        return ($this->_limit ? ceil($this->_total / $this->_limit) : 0);
    }


    ////////////////////////////////////////////////////////////////////
    /// @brief set a value at index
    ///
    /// @param $offset offset value to set
    /// @param $value value
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->_items[] = $value;
        } else {
            $this->_items[$offset] = $value;
        }
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief check if an offset exists
    ///
    /// @param $offset offset name
    /// @return bool if offset exists
    ////////////////////////////////////////////////////////////////////
    public function offsetExists($offset) {
        return isset($this->_items[$offset]);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief unset an offset
    ///
    /// @param $offset offset name
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function offsetUnset($offset) {
        unset($this->_items[$offset]);
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get an offset value
    ///
    /// @param $offset offset name
    /// @return value
    ////////////////////////////////////////////////////////////////////
    public function offsetGet($offset) {
        return isset($this->_items[$offset]) ? $this->_items[$offset] : null;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief rewind array pointer
    ///
    /// @return self
    ////////////////////////////////////////////////////////////////////
    function rewind() {
        reset($this->_items);
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief current array pointer
    ///
    /// @return self
    ////////////////////////////////////////////////////////////////////
    function current() {
        $var = current($this->_items);
        return (is_array($var) ? b::bucket($var) : $var);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief array key pointer
    ///
    /// @return key
    ////////////////////////////////////////////////////////////////////
    function key() {
          $var = key($this->_items);
        return $var;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief advance array pointer
    ///
    /// @return current value
    ////////////////////////////////////////////////////////////////////
    function next() {
        $var = next($this->_items);
        return $var;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief is the current array pointer valid
    ///
    /// @return current value
    ////////////////////////////////////////////////////////////////////
    function valid() {
        $var = $this->current() !== false;
        return $var;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get count of data
    ///
    /// @return count
    ////////////////////////////////////////////////////////////////////
    function count() {
        return count($this->_items);
    }

}


