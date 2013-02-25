<?php

namespace bolt\example\browser\controllers;
use \b;

// route
b::route(array('*','homepage/{name}'), '\bolt\example\browser\controllers\homepage');

// homepage class
class homepage extends \bolt\browser\controller {

    public function get($name=false) {

        // laout
        $this->setLayout('layout');

        $this->testParam = 'test';

        $this->response->headers->set('x-test', 'out');

        $this->meta = array(
            'title' => 'hello world'
        );

        $this->renderTemplate("homepage");

    }

}