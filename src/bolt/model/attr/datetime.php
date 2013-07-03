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

    const NAME = 'datetime';

    public function init() {
        $this->_dt = new DateTime();
        $this->_format = $this->cfg('format', DateTime::ISO8601);
    }

    public function get() {
        return $this->_dt;
    }

    public function set($value) {
        if (is_numeric($value)) {
            $this->_dt->setTimestamp($value);
        }
        else {
            $this->_dt->modify($value);
        }
        return $this;
    }

    public function normalize() {
        return $this->_dt->format($this->_format);
    }

    public function value() {
        return $this->normalize();
    }

}