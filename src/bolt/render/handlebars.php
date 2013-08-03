<?php

namespace bolt\render;
use \b;

class handlebars extends base {

    public static $extension = array('hbr');

    private $_eng = false;

    public function __construct() {

        $this->_eng = new \Handlebars_Engine(array(
            'delimiter' => "<% %>",
            'escape' => function($value) {
                return (is_string($value) ? htmlentities($value, ENT_QUOTES, 'UTF-8', false) : $value);
            }
        ));

        // add our default helpers
        $this->_addHelpers();
    }

    public function render($str, $vars=array()) {

        // load any unload partials
        foreach ($this->getPartials() as $name => $file) {
            if (!array_key_exists($name, $this->_eng->_partials)) {
                $this->_eng->_partials[$name] = $file;
            }
        }

        // helpers
        foreach ($this->getHelpers() as $name => $cb) {
            $this->_eng->addHelper($name, $cb[0]);
        }

        // make sure variables are a bucket
        if (!b::isInterfaceOf($vars, '\bolt\iBucket')) {
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
            },
            'b' => function($template, $context, $args, $text) {
                $args = explode(' ', $args);

                $plugins = explode('.', array_shift($args));
                $last = array_pop($plugins);
                $b = b::bolt();
                foreach ($plugins as $plug) {
                    $b = call_user_func(array($b, $plug));
                }
                if (!is_object($b) OR !method_exists($b, $last)) {return;}
                return call_user_func_array(array($b, $last),$args);
            }
        );
        foreach ($helpers as $name => $cb) {
            $this->_eng->addHelper($name, $cb);
        }
    }

}