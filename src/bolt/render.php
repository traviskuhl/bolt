<?php

namespace bolt;
use \b;

// render
b::plug('render', '\bolt\render');

class render extends plugin\singleton {

    private $_helpers = array();
    private $_paritals = array();
    private $_globals = array();
    private $_renderers = array();

    public function __construct() {
        $render = b::getDefinedSubClasses('\bolt\render\base');

        // get renders
        foreach ($render as $class) {
            if ($class->hasProperty('extension')) {
                $this->_renderers[$class->name] = array('instance' => false, 'ext' => $class->getProperty('extension')->getValue());
            }
        }

        // partials to load
        if (b::config()->exists('project.partials')) {
            $dir = b::config()->value('project.partials');
            if (file_exists($dir)) {
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $item) {
                    if ($item->isFile()) {
                        $name = trim(str_replace(array($dir, '.template', '.'.$item->getExtension()), '', $item->getPathname()), '/');
                        $this->partial($name, $item->getPathname());
                    }
                }
            }
        }

    }

    public function _default($args=array()) {
        if (count($args) > 0) {
            return $this->_render($args);
        }
        return $this;
    }

    public function helper($name, $callback) {
      $this->_helpers[$name] = array($callback);
      return $this;
    }

    public function partial($name, $file) {
      $this->_paritals[$name] = $file;
      return $this;
    }

    public function variable($name, $var) {
      $this->_globals[$name] = $var;
      return $this;
    }

    public function getRenderer($ext) {
        foreach ($this->_renderers as $class => $r) {
            if (in_array($ext, $r['ext'])) {
                if (!$r['instance']) {
                    $this->_renderers[$class]['instance'] = new $class();
                }
                return $this->_renderers[$class]['instance'];
            }
        }
        return false;
    }

    private function _render($args) {

        $this->fire('before');

        $file = (isset($args['file']) ? $args['file'] : false);;
        $string = (isset($args['string']) ? $args['string'] : false);;
        $vars = (isset($args['vars']) ? $args['vars'] : array());
        $self = (isset($args['self']) ? $args['self'] : false);
        $renderer = (isset($args['renderer']) ? $args['renderer'] : array());

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

        // uniq
        $vars["_buid"] = uniqid("b");

        // if we have a file, lets try to load it
        if ($file) {

            // no file
            if (!file_exists($file)) {
                return '';
            }

            // string
            $string = file_get_contents($file);

            // get render extensions
            // and shift off the first
            $renderer = explode(".", $file); array_shift($renderer);

        }

        // it's a string
        if (is_string($renderer)) {
            $renderer = array($renderer);
        }

        foreach ($renderer as $ext) {
            $render = $this->getRenderer($ext);

            if ($render) {

                // update our helpers and parials
                $render->set($this->_helpers, $this->_paritals);

                // string
                $string = $render->render($string, $vars);

            }
        }

        return $string;

    }

}


