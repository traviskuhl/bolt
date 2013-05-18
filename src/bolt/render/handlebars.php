<?php

namespace bolt\render;
use \b;

// render
b::render()->plug('handlebars', '\bolt\render\handlebars');

//

// handlebars
class handlebars extends \bolt\plugin\singleton {

  private $eng;
  private $_helpers = array();
  private $_partials = array();

  public function partial($name, $file) {
    $this->_partials[$name] = $file;
  }

  public function __construct() {

    // // auto load
    // \Handlebars_Autoloader::register(bPear.'/bolt/vendor/');

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
          'view' => function($template, $context, $args, $text) {
            $parts = explode(' ', $args);
            $class = trim(array_shift($parts), '"\'');
            $params = array();

            if (trim($text) AND is_array(json_decode(trim($text), true))) {
              $params += array_merge($params, json_decode(trim($text), true));
            }
            else if ($parts) {
              $params = json_decode(trim(implode(" ", $parts)), true);
            }

            $v =  b::view($class)->setParams($params);

            if ($context->get('controller')) {
              $v->setController($context->get('controller'));
            }
            return $v->render();
          },
          'url' => function($template, $context, $args, $text) {

            if (empty($args)) {return;}

            // get all our context params an see if they exist in the string
            if (preg_match_all('#\$([a-zA-Z0-9_\.]+)#', $args, $matches, PREG_SET_ORDER)) {
              foreach ($matches as $match) {
                $val = $context->get($match[1]);
                if (!$val AND $context->get('controller')) {
                  $val = $context->get('controller')->getParamValue($match[1]);
                }
                $args = str_replace($match[0], $val, $args);
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
          },
          '=' => function($template, $context, $args, $text) {
            if (preg_match_all('#\$([a-zA-Z0-9_\.]+)#', $args, $matches, PREG_SET_ORDER)) {
              foreach ($matches as $match) {
                $val = $context->get($match[1]);
                if (!$val AND $context->get('controller')) {
                  $val = $context->get('controller')->getParamValue($match[1]);
                }
                // no val
                if (!$val) { return ""; }
                if (!is_object($val)) { $val = b::bucket($val); }

                ${$match[1]} = $val;
              }
            }
            if (substr($args,-1) !== ';') { $args .=';'; }
            return eval("return $args");

          }
        )
      )
    ));

    if (b::config()->exists('project.partials')) {
      $dir = b::config()->getValue('project.partials');
      foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $item) {
        if ($item->isFile()) {
          $name = trim(str_replace(array($dir, '.template', '.'.$item->getExtension()), '', $item->getPathname()), '/');
          $this->partial($name, $item->getPathname());
        }
      }
    }


  }


  public function render($str, $vars=array()) {

    foreach ($this->_partials as $name => $file) {
      if (!array_key_exists($name, $this->eng->_partials)) {
        $this->eng->_partials[$name] = $file;
      }
    }

    if (!is_a($vars, '\bolt\bucket')) {
      $vars = b::bucket($vars);
    }

    // preprocess the text for echo calls
    if (preg_match_all('#\<\%=\s?([^%]+)\s?\%>#i', $str, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $str = str_replace($match[0], call_user_func(function($call, $_vars, $helpers){
          if (substr($call,-1) != ';') { $call .= ';';}


          foreach ($_vars as $k => $v) {
            $$k = (is_object($v) ? $v : b::bucket($v));
          }

          if ($_vars->exists('self')) {
            $self = $_vars['self'];
          }

          $str =  eval(trim('return '.$call));

          if (is_string($str)) {
            return $str;
          }
          else if (is_a($str, '\bolt\bucket') OR is_a($str, '\bolt\bucket\bString')) {
            return (string)$str;
          }

        }, trim($match[1]), $vars, $this->_helpers), $str);
      }
    }

    try {
      $str = $this->eng->render($str, $vars);
    }
    catch(LogicException $e) { return; }

    return $str;

  }

}
