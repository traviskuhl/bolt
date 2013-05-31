<?php

namespace bolt;
use \b;

// render
b::plug('render', '\bolt\render');

class render extends plugin {

    // factory
    public static $TYPE = 'singleton';

    private $_helpers = array();
    private $_globals = array();

    public function __construct() {


    }

    public function _default($args=array()) {
        if (count($args) > 0) {
            $render = b::render()->call(b::param('render', 'handlebars', $args));
            return $this->_render($render, $args);
        }
        return $this;
    }

    public function helper($name, $callback) {
      $this->_helpers[$name] = array($callback);
      return $this;
    }
    public function variable($name, $var) {
      $this->_globals[$name] = $var;
      return $this;
    }

    private function _render($render, $args) {

        $this->fire('before');

        $file = (isset($args['file']) ? $args['file'] : false);;
        $string = (isset($args['string']) ? $args['string'] : false);;
        $vars = (isset($args['vars']) ? $args['vars'] : array());
        $self = (isset($args['self']) ? $args['self'] : false);

        if ($self) {
            $vars['self'] = $self;
           foreach ($self->getParams() as $key => $param)  {
               if (!array_key_exists($key, $vars)) {
                   $vars[$key] = $param;
               }
           }
        }

        // render helpers & globals
        foreach ($this->_globals as $name => $var) {
          $vars[$name] = $var;
          $vars["_{$name}"] = $var;
        }
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


