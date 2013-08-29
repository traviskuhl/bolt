<?php

namespace bolt\model\attr;
use \b;
use \DateTime;
use \DateTimeZone;
use \DateInterval;
use \DatePeriod;

class attr_datetime extends \bolt\model\attr\base {
    private $_dt = false;
    private $_format = false;
    private $_default = false;

    const NAME = 'datetime';

    public function init() {
        $this->_format = $this->cfg('format', DateTime::ISO8601);
        $this->_default = $this->cfg('default', false);
        $this->_dt = new \bolt\bucket\bDateTime(false);
    }

    public function get() {
        return ($this->_value ? $this->_dt : false);
    }

    public function set($value) {
        $this->_value = $value;
        $this->_dt->set($value);
        if ($value AND is_numeric($value)) {
            $this->_dt->setTimestamp($value);
        }
        else if ($value) {
            $this->_dt->modify($value);
        }
        return $this;
    }

    public function normalize() {
        if (!$this->_value AND $this->_default) {
            $this->set($this->_default);
        }
        return ($this->_value ? $this->_dt->format($this->_format) : false);
    }

    public function value() {
        return $this->normalize();
    }

}