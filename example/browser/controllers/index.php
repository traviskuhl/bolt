<?php

namespace bolt\example\browser\controllers;
use \b;

// route
b::route('*', '\bolt\example\browser\controllers\example');

// example class
class example extends \bolt\browser\controller {

    public function get() {

        // laout
        $this->setLayout('layout');

        $this->testParam = 'test';

        $this->request->headers->add('test', 'out');

        $this->render("example");

    }

}