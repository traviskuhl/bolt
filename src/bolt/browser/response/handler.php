<?php

namespace bolt\browser\response;
use \b;

abstract class handler extends \bolt\plugin\factory {
    private $_type = false;
    private $_content = false;
    private $_data = array();
    private $_status = 0;

    public function handle() {
        return $this->_content;
    }

    public function setContentType($type) {
        $this->_type = $type;
        return $this;
    }
    public function getContentType(){
        return $this->_type;
    }

    public function setContent($content) {
        $this->_content = $content;
        return $this;
    }
    public function getContent() {
        return $this->_content;
    }

    public function setData($data) {
        $this->_data = (array)$data;
        return $this;
    }
    public function getData(){
        return $this->_data;
    }

    public function setStatus($status) {
        $this->_status = (int)$status;
        return $this;
    }
    public function getStatus() {
        return $this->_status;
    }

}