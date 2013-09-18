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

    public static function defined($module) {
        if (array_key_exists($module, self::$_modules)) {return true;}
        return class_exists($module);
    }

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

    public function __invoke($act='build', $args=array(), $responseType=false) {
        parent::invoke($act, $args);

        if ($responseType === false) {
            $responseType = $this->getResponseType();
        }

        // get content
        $content = $this->getContent();

        if ($content === false) {

            // get from response
            $content = $this->getResponseByType($responseType);

            // nope
            if (!$content) {return;}

            // get the response
            $content = $content->getResponse();

        }

        // content is callabke
        if (is_callable($content)) {
            $content = $content();
        }

        return $content;
    }

}