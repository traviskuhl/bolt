<?php

namespace bolt\render;
use \b;


// render
b::render()->plug('mustache', '\bolt\render\mustache');

// mustache
class mustache extends \bolt\plugin\singleton {

	private $eng;

	public function __construct() {		

		// include
		require bRoot.'/vendor/Mustache/Autoloader.php';
		\Mustache_Autoloader::register(bRoot.'/vendor/');

		// engine
		$this->eng = new \Mustache_Engine(array(
				'escape' => function($value) {
					return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
				},                
        'helpers' => array(
            'boltJsEscape' => function($text) {                    
                return ($text ? '__:'.base64_encode($text).':__' : $text);
            }
          )
			));


	}

	public function render($str, $vars=array()) {			

    // render
    $s = microtime(true);
  
  // convert any oldschool {$xx} to {{name}}
      if (preg_match_all('/\{(\$[^\}]+)\}/', $str, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
          $str = preg_replace("#".preg_quote($match[0], '#')."#", $this->eng->render('{{'.trim($match[1],'$').'}}', $vars), $str, 1);
        }
      }

    // repalce
    $str = preg_replace(array("#<script([^>]+)?>#","#</script>#"), array("{{#boltJsEscape}}<script$1>","</script>{{/boltJsEscape}}"), $str);      

    // modules
		$_modules = b::render()->getModules();	

		// modules to execute
        if ( preg_match_all("/\{\%([^\}]+)\%\}/i", $str, $modules, PREG_SET_ORDER) ) {                 

          // go through modules
          foreach ($modules as $module) {

            // name of module
            $name = trim($module[1]);
            $args = array();
            $_vars = $vars;

            // if there's a ( we need to parse params
            if (stripos($module[1], '(') !== false AND preg_match("#\(([^\)]+)\)#", $module[1], $p)) {

              // get our parts
              $parts = explode(",", $p[1]);
              foreach ($parts as $val) { 
                if (stripos($val, ':') === false)  {
                  $args[] = trim($val);
                }
                else {                
                  list($k, $v) = explode(":", trim($val));
                  $_vars[trim($k)] = trim($v);
                }
              }

              // reset name
              $name = trim(str_replace($p[0], "", $name));

            }

            $content = "";

            // see if we have this module
            if (array_key_exists($name, $_modules)) { 
              $content = b::module($name, $_vars, $args);
            }            
            
            // replace
            $str = preg_replace("#".preg_quote($module[0], '#')."#", $content, $str, 1);

          }  
        }
      

      $str = $this->eng->render($str, $vars);      
    
      error_log("mustache render time: ".(microtime(true)-$s));

		return $str;
	}

  public function finalize($str) {
    if(preg_match_all("#__:([^:]+)?:__#", $str, $matches, PREG_SET_ORDER)) { 
        foreach ($matches as $match) {
          $str = str_replace($match[0], base64_decode($match[1]), $str);
        }        
    }
    return $str;
  }

}
