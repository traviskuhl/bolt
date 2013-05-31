<?php

namespace bolt\browser\controller;
use \b;


// add our render helper on run
b::render()->once('before', function() {

    // render
    b::render()->handlebars->helper('module', function($template, $context, $args, $text) {

        $parts = explode(' ', $args);
        $class = trim(array_shift($parts), '"\'');
        $params = array();
        if (trim($text) AND is_array(json_decode(trim($text), true))) {
            $params += array_merge($params, json_decode(trim($text), true));
        }
        else if ($parts) {
            $params = json_decode(trim(implode(" ", $parts)), true);
        }
        $v =  b::controller($class)->setParams($params);
        if ($context->get('controller')) {
            $v->setController($context->get('controller'));
        }
        return $v->render();
    });

});


class module extends \bolt\browser\controller {

    protected $_action = 'build';

}