<?php

namespace bolt\browser\controller;
use \b;


// add our render helper on run
b::on('run', function() {

    // find all named modules
    $modules = b::getDefinedSubClasses('\bolt\browser\controller\module');

    foreach ($modules as $module) {
        if (!$module->hasConstant('NAME')) {continue;}
        $name = $module->getConstant('NAME');
        module::$_modules[$name] = $module->name;
    }

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