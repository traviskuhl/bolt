<?php

namespace bolt\render;
use \b;

// render
b::render()->plug('handlebars', '\bolt\render\handlebars');

// handlebars
class handlebars extends \bolt\plugin\singleton {

  private $eng;
  private $_helpers = array();
  private $_partials = array();

  public function partial($name, $file) {
    $this->_partials[$name] = $file;
  }

  public function __construct() {

    // include
    require bRoot.'/vendor/Handlebars/Autoloader.php';

    // auto load
    \Handlebars_Autoloader::register(bRoot.'/vendor/');

    // engine
    $this->eng = new \Handlebars_Engine(array(
      'delimiter' => "<% %>",
      'escape' => function($value) {
        if (is_string($value)) {
          return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
        }
      },
      'partials' => $this->_partials,
      'helpers' => new \Handlebars_Helpers(array(
          'escape' => function($template, $context, $args, $text) {
            if (is_string($text)) {
              return htmlentities($text, ENT_QUOTES, 'UTF-8', false);
            }
            return false;
          },
          'url' => function($template, $context, $args, $text) {
            if (empty($args)) {return;}

            // get all our context params an see if they exist in the string
            if (preg_match_all('#\$([a-zA-Z0-9_\.]+)#', $args, $matches, PREG_SET_ORDER)) {
              foreach ($matches as $match) {
                $args = str_replace($match[0], $context->get('controller')->getParamValue($match[1]), $args);
              }
            }

            $parts = explode(",", trim($args));

            if (count($parts) == 0) return;
            $name = trim(array_shift($parts));
            $params = array();
            $query = array();

            foreach ($parts as $part) {
              list($key, $value) = explode("=", trim($part));
              if ($key == 'query') {
                $query = json_decode($value, true);
              }
              else {
                $params[$key] = $value;
              }
            }

            return b::url($name, $params, $query);


          }
        )
      )
    ));




  }


  public function render($str, $vars=array()) {

    foreach ($this->_partials as $name => $file) {
      if (!array_key_exists($name, $this->eng->_partials)) {
        $this->eng->_partials[$name] = $file;
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

    if (!is_a($vars, '\bolt\bucket')) {
      $vars = b::bucket($vars);
    }

    try {
      $str = $this->eng->render($str, $vars);
    }
    catch(LogicException $e) { return; }


    return $str;

  }

}
