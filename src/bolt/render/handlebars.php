<?php

namespace bolt\render;
use \b;

b::render()->plug('handlebars', '\bolt\render\handlebars');

class handlebars extends \bolt\plugin\singleton {

    private $_eng = false;
    private $_partials = array();
    private $_helpers = array();

    public function __construct() {
        $this->_eng = new \Handlebars_Engine(array(
            'delimiter' => "<% %>",
            'escape' => function($value) {
                return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
            }
        ));

        // partials to load
        if (b::config()->exists('project.partials')) {
            $dir = b::config()->getValue('project.partials');
            if (file_exists($dir)) {
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $item) {
                    if ($item->isFile()) {
                        $name = trim(str_replace(array($dir, '.template', '.'.$item->getExtension()), '', $item->getPathname()), '/');
                        $this->partial($name, $item->getPathname());
                    }
                }
            }
        }

        // add our default helpers
        $this->_addHelpers();
    }

    public function partial($name, $file) {
        $this->_partials[$name] = $file;
    }

    public function helper($name, $cb) {
        $this->_helpers[$name] = $cb;
    }

    public function render($str, $vars=array()) {

        // load any unload partials
        foreach ($this->_partials as $name => $file) {
            if (!array_key_exists($name, $this->_eng->_partials)) {
                $this->_eng->_partials[$name] = $file;
            }
        }

        // helpers
        foreach ($this->_helpers as $name => $file) {
            $this->_eng->addHelper($name, $file);
            unset($this->_helpers[$name]);
        }

        // make sure variables are a bucket
        if (!is_a($vars, '\bolt\bucket')) {
            $vars = b::bucket($vars);
        }

        // try to render the string
        try {
            $str = $this->_eng->render($str, $vars);
        }
        catch(LogicException $e) { return; }

        // give it back
        return $str;

    }

    private function _addHelpers() {
        $helpers = array(
            '=' => function($template, $context, $args, $text) {
                if (preg_match_all('#\$([a-zA-Z0-9_\.]+)#', $args, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $val = $context->get($match[1]);
                        if (!$val AND $context->get('controller')) {
                            $val = $context->get('controller')->getParamValue($match[1]);
                        }
                        if (!$val) { return ""; }
                        if (!is_object($val)) { $val = b::bucket($val); }
                        ${$match[1]} = $val;
                    }
                }
                var_dump($args); die;
                if (substr($args,-1) !== ';') { $args .=';'; }
                return eval("return $args");
            }
        );
        foreach ($helpers as $name => $cb) {
            $this->_eng->addHelper($name, $cb);
        }
    }

}