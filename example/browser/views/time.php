<?php

namespace bolt\example\browser\views;
use \b;

class time extends \bolt\browser\view {

    public function init() {

    }
    public function build($ts=false) {


        $ts = $this->params->getValue('ts', ($ts ?: time()));

        $this->setContent("The current Time is ".date('h:m a', $ts));

        return $this;

    }


}