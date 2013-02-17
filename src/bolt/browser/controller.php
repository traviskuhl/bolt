<?php

namespace bolt\browser;
use \b;

interface controller {

}

class controller {

    private $_layout;
    private $_params;
    private $_view;

    public function __construct() {
        $this->_params = b::bucket();
    }


    public function __set($name, $value) {
        $this->_params->set($name, $value);
    }

    public function __get($name) {
        return $this->_params->get($name);
    }

    public function getParams() {
        return $this->_params;
    }


    public function setLayout($layout) {
        if (is_string($layout)) {
            $layout = new \bolt\browser\view();
            $layout->setFile($layout);
        }
        $this->_layout = $layout;
        return $this;
    }

    public function getLayout($layout) {
        return $this->_layout;
    }

    public function setView($view) {
        $this->_view = $view;
        return $this;
    }

    public function getView() {
        return $this->_view;
    }

    public function render($view) {

        if (is_string($view)) {

            // view directory
            $viewDir = b::_("views");
            $file = $view;

            // does it have a .template.php ext
            if (stripos($file, '.template.php') === false) {
                $file .= '.template.php';
            }

            // new view
            $view = new \bold\browser\view();

            // is it a file we cna find
            if (file_exists($file)) {
                $view->setFile($file);
            }
            else if (file_exists($viewDir . $file)) {
                $view->setFile($viewDir . $file);
            }
            else {
                $view->setContent($view);
            }

        }

        // make sure view implements bolt\browser\view
        if (($implements = class_implements($view)) !== false AND in_array('\bolt\browser\view', $implements)) {
            return false;
        }

        // set our view
        $this->setView($view);

    }

    // get content
    public function getContent() {


    }

}