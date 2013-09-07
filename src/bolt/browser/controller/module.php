<?php

namespace bolt\browser\controller;
use \b;


// add our render helper on run
b::render()->once('before', function() {

    // find all named modules
    $modules = b::getDefinedSubClasses('\bolt\browser\controller\module');

    foreach ($modules as $module) {
        if (!$module->hasConstant('NAME')) {continue;}
        $name = $module->getConstant('NAME');
        module::$_modules[$name] = $module->name;
    }

    // // render
    // b::render()->helper('module', function($template, $context, $args, $text) {
    //     $args = explode(' ', trim($args));
    //     $name = array_shift($args);
    //     $class = false;
    //     if (array_key_exists($name, module::$_modules)) {
    //         $class = module::$_modules[$name];
    //     }
    //     else if (class_exists($name)) {
    //         $class = $name;
    //     }
    //     else {
    //         return '';
    //     }
    //     $params = b::bucket(array());
    //     if ($context->get('self')) {
    //         $params = $context->get('self')->getParams();
    //     }
    //     if (count($args)) {
    //         foreach ($args as $arg) {
    //             if (stripos($arg, '=') !== false) {
    //                 list($k, $v) = explode('=', $arg);
    //                 $params[$k] = $v;
    //             }
    //         }
    //     }
    //     if (!empty($text) AND ($json = json_decode(trim($text), true)) !== null) {
    //         foreach ($json as $k => $v) {
    //             $params[$k] = $v;
    //         }
    //     }
    //     $mod = new $class();
    //     return $mod->render($params);
    // });

});



class module extends \bolt\browser\controller {

    static $_modules = array();

    protected $_action = 'build';

    private $_content = false;

    public function getContent() {
        return $this->_content;
    }

    protected function setContent($content) {
        $this->_content = $content;
        return $this;
    }

    public function __invoke($act='build', $args=array()) {
        parent::invoke($act, $args);
        return $this->getContent();
    }

}