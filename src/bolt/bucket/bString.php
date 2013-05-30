<?php

namespace bolt\bucket;
use \b;

class bString implements \bolt\iBucket {

    private $_bguid = false;
    private $_original = null;
    private $_value = null;
    private $_parent = false;
    private $_key = false;


    ////////////////////////////////////////////////////////////////////
    /// @brief construct a bucket string
    ///
    /// @param $key name of key
    /// @param $value starter value
    /// @param $parent bucket pointer
    /// @return void
    ////////////////////////////////////////////////////////////////////
    public function __construct($value, $key, $parent) {
        $this->_bguid = uniqid('b');
        $this->_value = $this->_original = $value;
        $this->_key = $key;
        $this->_parent = $parent;
    }

    public function bGuid() {
        return $this->_bguid;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief map values in an array
    ///
    /// @param $name name of modifier
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function __get($name) {
        if ($name == 'value') {
            return $this->_value;
        }
        else if ($name == 'original') {
            return $this->_original;
        }
        else if (method_exists($this, $name)) {
            return $this->$name();
        }
        return $this->get();
    }

    public function __set($name, $value) {
        $this->set($value);
    }

    public function __call($name, $args) {
        return $this->_value;
    }

    public function __toString() {
        return (string)$this->_value;
    }

    public function __isset($name) {
        return true;
    }

    public function value() {
        return $this->_value;
    }
    public function normalize() {
        return $this->_value;
    }

    public function get($default=false) {
        return $this;
    }

    public function set($value) {
        if (is_array($value)) {
            $o = new \bolt\bucket\bArray($value, $this->_key, $this->_parent);
            $this->_parent->set($this->_key, $o);
            return $o;
        }
        $this->_value = $value;
        if ($this->_parent) {
            //$this->_parent->set($this->_key, $value);
        }
        return $this;
    }

    public function isTrue() {
        return $this->_value === true;
    }

    public function isFalse() {
        return $this->_value === false;
    }

    // string functions
    public function ago() {
        $this->_value = b::ago($this->_value);
        return $this;
    }

    public function date($fmt) {
        $this->_value = date($fmt, $this->_value);
        return $this;
    }

    public function strip_tags() {
        $this->_value = strip_tags($this->_value);
        return $this;
    }

    public function short($len, $onwords=true, $append=false) {
        $this->_value = b::short($this->_value, $len, $onwords, $append);
        return $this;
    }
    public function encode($q=ENT_QUOTES) {
        $this->_value = htmlentities($this->_value, $q, 'utf-8', false);
        return $this;
    }
    public function decode($q=ENT_QUOTES) {
        $this->_value = html_entity_decode($this->_value, $q, 'utf-8');
        return $this;
    }
    public function toUpper() {
        $this->_value = strtoupper($this->_value);
        return $this;
    }
    public function toLower() {
        $this->_value = strtolower($this->_value);
        return $this;
    }
    public function ucfirst() {
        $this->_value = ucfirst($this->_value);
        return $this;
    }
    public function cast($type) {
        settype($this->_value, $type);
        return $this;
    }
    public function totime() {
        $this->_value = strtotime($this->_value);
        return $this;
    }

    public function exists() {
        return true;
    }

}