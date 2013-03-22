<?php

namespace bolt\render;
use \b;

// render
b::render()->plug('handlebars', '\bolt\render\handlebars');

// handlebars
class handlebars extends \bolt\plugin\singleton {

  private $eng;
  private $_helpers = array();

  public function __construct() {

    // include
    require bRoot.'/vendor/Handlebars/Autoloader.php';

    // auto load
    \Handlebars_Autoloader::register(bRoot.'/vendor/');

    // engine
    $this->eng = new \Handlebars_Engine(array(
      'delimiter' => "<% %>",
      'escape' => function($value) {
        return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
      },
      'helpers' => new \Handlebars_Helpers(array(
          'escape' => function($template, $context, $args, $text) {
            return htmlentities($text, ENT_QUOTES, 'UTF-8', false);
          }
        )
      )
    ));



  }


  public function render($str, $vars=array()) {

    // preprocess the text for bolt function calls
    if (preg_match_all('#\<\%\s?(b\::[^%]+)\s?\%>#i', $str, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $str = str_replace($match[0], call_user_func(function($call, $_vars){
          if (substr($call,-1) != ';') { $call .= ';';}
          foreach ($_vars as $k => $v) {
            $$k = $v;
          }
          return eval(trim('return '.$call));
        }, trim($match[1]), $vars), $str);
      }
    }

    // preprocess the text for echo calls
    if (preg_match_all('#\<\%=\s?([^%]+)\s?\%>#i', $str, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $str = str_replace($match[0], call_user_func(function($call, $_vars, $helpers){
          if (substr($call,-1) != ';') { $call .= ';';}
          foreach ($_vars as $k => $v) {
            $$k = $v;
          }
          $str =  eval(trim('return '.$call));
          return $str;
        }, trim($match[1]), $vars, $this->_helpers), $str);
      }
    }

    try {
      $str = $this->eng->render($str, (is_object($vars) ? $vars : b::bucket($vars)));
    }
    catch(LogicException $e) { return; }

    return $str;

  }

}
