<?php

namespace bolt\example\browser\controllers;
use \b;

// homepage class
class homepage extends \bolt\browser\controller {

    static $routes = array(
        array('route' => '*'),
        array('route' => 'hello/{name}', "name" => 'hello', 'action' => 'name', 'validate' => array('name' => '[a-zA-Z\s]+'))
    );

    protected $layout = 'layout';

    public function getTemplateDir() {
        return __DIR__."/../templates";
    }

    public function init() {

        $this->meta = array(
            'title' => 'hello world'
        );

    }

    public function get() {

        $this->testParam = 'test';

        $this->response->headers->set('x-test', 'out');

        $this->name = 'who knows';


        $this->renderTemplate("homepage");

    }

    public function getName($name) {


        $this->renderTemplate("homepage");

    }

}