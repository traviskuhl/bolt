<?php

namespace bolt\browser\controller;
use \b;

class redirect extends \bolt\browser\controller {

    private $_url;

    public function setUrl($url) {
        $this->_url = $url;
        return $this;
    }

    public function invoke() {
        return b::location($this->_url);
    }

}

