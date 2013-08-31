<?php

namespace bolt\bucket;
use \b;

// dateTime shortcuts
use \DateTime;
use \DateTimeZone;
use \DateInterval;
use \DatePeriod;

/**
 * bucket array wrapper
 */
class bDateTime implements \bolt\iBucket {

    private $_value;
    private $_root;
    private $_parent;
    private $_dt;
    private $_format;

    public function __construct($data, $root=false, $parent=false) {
        $this->_value = $data;
        $this->_dt = new DateTime();
        $this->set($data);
        $this->_format = DateTime::ISO8601;
    }


    public function set($value) {
        $this->_value = $value;

        if ($value AND is_numeric($value)) {
            $this->_dt->setTimestamp($value);
        }
        else if ($value) {
            $this->_dt->modify($value);
        }
    }

    public function get() {
        return ($this->_value ? $this->_dt : false);
    }

    public function ago() {
        return b::ago($this->_dt->getTimestamp());
    }
    public function left() {
        return b::left($this->_dt->getTimestamp());
    }

    public function __get($name) {
        if ($name == 'value') {
            return $this->value();
        }
        else if (property_exists($this->_dt, $name)) {
            return $this->_dt->$name;
        }
        return false;
    }

    public function __call($name, $args) {
        if (method_exists($this->_dt, $name)) {
            return call_user_func_array(array($this->_dt, $name), $args);
        }
        return $this;
    }

    public function value() {
        return ($this->_value ? $this->_dt->format($this->_format) : false);
    }

    public function isAfter($when='now') {
        return ($this->_dt->getTimestamp() < strtotime($when));
    }

}
