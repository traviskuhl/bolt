<?php

namespace bolt\dao;
use \b;

class result extends \bolt\bucket {

    // private
    private $_guid; /// guid for unique objects
    private $_loaded = false;
    private $_meta;
    private $_total = 0;
    private $_limit = 0;
    private $_offset = 0;

    public static function create($class, $items, $key='id') {
        $result = new result();
        $result->setItems($items, $key, $class);
        return $result;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief construct a result
    ///
    /// @return void
    ////////////////////////////////////////////////////////////////////
    public function __construct($items=false, $key='id') {
        if (is_array($items)) {
            $this->setItems($items, $key);
        }
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
        return call_user_func(array($this->_meta, '__get'), $name);
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

    ////////////////////////////////////////////////////////////////////
    /// @brief has this item been loaded
    ///
    /// @return bool if item is loaded
    ////////////////////////////////////////////////////////////////////
    public function loaded() {
        return $this->_loaded;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set the items
    ///
    /// @param $items array of item objects
    /// @param $key name of item key
    /// @param $class class name
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setItems($items, $key='id', $class=false) {
        foreach ($items as $item) {
            if (!is_a($item, $class)) {
                $item = new $class($item);
            }
            $this->push($item, (string)$item->getValue($key));
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

}


