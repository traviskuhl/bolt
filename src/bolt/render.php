<?php

namespace bolt;

use \b as b;

// render
b::plug('render', '\bolt\render');

class render extends plugin {

    // factory
    public static $TYPE = 'singleton';

    private $_helpers = array();

    public function __construct() {
        // render ehlper
        $this->helper('view', function($args, $vars){
          $view = b::view(array_shift($args));
          $view->setParams($vars);
          return $view->render($args);
        });

    }

    public function _default($args=array()) {
        if (count($args) > 0) {
            $render = b::render()->call(p('render', 'handlebars', $args));
            return $this->_render($render, $args);
        }
        return $this;
    }

    public function helper($name, $callback) {
      $this->_helpers[$name] = array($callback);
    }

    public function view($view, $params=array()) {

        // string
        if (is_string($view) AND class_exists($view, true)) {
            $view = b::view($view);
        }

        // make sure view implements bolt\browser\view
        if (!b::isInterfaceOf($view, '\bolt\browser\iView')) {
            return false;
        }

        // set our view
        return $view
                ->setParams($params)
                ->render();

    }

    private function _render($render, $args) {

        $file = (isset($args['file']) ? $args['file'] : false);;
        $string = (isset($args['string']) ? $args['string'] : false);;
        $vars = (isset($args['vars']) ? $args['vars'] : array());
        $controller = (isset($args['controller']) ? $args['controller'] : false);


        if ($controller) {
            $vars['controller'] = $controller;
           foreach ($controller->getParams() as $key => $param)  {
               if (!array_key_exists($key, $vars)) {
                   $vars[$key] = $param;
               }
           }
        }

        // render helpers
        foreach ($this->_helpers as $name => $helper) {
          $vars[$name] = function() use ($helper, $vars){
            return call_user_func_array($helper[0], array(func_get_args(), $vars));
          };
        }

        // if we have a file, lets try to load it
        if ($file) {

            if (stripos($file, '.template.php') === false) {
                $file .= '.template.php';
            }

            // render in a callback to control scope
            $string = call_user_func(function($_file, $_vars){

                // start the buffer
                ob_start();

                // globalize our variables
                foreach ($_vars as $k => $v) {
                    $$k = (is_object($v) ? $v : b::bucket($v));
                }

                // include our file
                include($_file);

                // content and clean
                $content = ob_get_contents(); ob_clean();

                return $content;

            }, $file, $vars);

        }

        // pass to the render
        return $render->render($string, $vars);

    }

}


