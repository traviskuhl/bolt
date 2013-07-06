<?php

namespace bolt\render;
use \b;

interface iRender {

}

abstract class base implements iRender {

    private $_helpers = array();
    private $_partials = array();

    public function set($helpers, $partials) {
        $this->_helpers = $helpers;
        $this->_partials = $partials;
    }

    public function getHelpers() {
        return $this->_helpers;
    }

    public function getPartials() {
        return $this->_partials;
    }

}