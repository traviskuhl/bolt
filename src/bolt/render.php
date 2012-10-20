<?php

namespace bolt;

use \b as b;

// render
b::plug(array(
    'render' => '\bolt\render',
    'template' => 'render::template',
    'module' => function() {
        return call_user_func_array(array(b::render(), 'module'), func_get_args());
    }
));

class render extends plugin {

    // factory
    public static $TYPE = 'singleton';

    // global args
    private $_globals = array();
    
    // modules
    private $_modules = array();

    // default
    public function _default($args=array()) {
    
        // render a template
        if (isset($args['template'])) {
            $tmpl = $args['template']; unset($args['template']);
            return $this->template($tmpl, $args, $args['view']);
        }
        
        // render a string
        else if (isset($args['string'])) {
            $str = $args['string']; unset($args['string']);        
            return $this->string($str, $args, $args['view']);
        }
        
        // render a module
        else if (isset($args['module'])) {
            $mod = $args['module']; unset($args['module']);                
            return $this->module($mod, $args, $args['view']);
        }
        
        // who the hell knows
        // assume the args are just args
        else {
            
            // set args and return me
            $this->_globals += $args;
            
            // this is em
            return $this;
            
        }
        
    }

    // __get
    public function __get($key) {
        return $this->get($key);
    }
    
    // __set
    public function __set($key, $value) {
        $this->set($key, $value);
    }
    
    public function get($key) {
        return (array_key_exists($key, $this->_globals) ? $this->_globals[$key] : false);
    }
    
    // global 
    public function set() {
        if (is_string(func_get_arg(0))) {
            $key = func_get_arg(0);
            $value = func_get_arg(1);        
            $this->_globals[$key] = $value;
        }
        else {        
            foreach (func_get_args() as $array) {
                foreach ($array as $key => $valuee) {
                    $this->_globals[$key] = $value;
                }
            }
        }
    }

    public function register($name, $class, $type='factory') {
      
        // add it 
        $this->_modules[$name] = array(
            'class' => $class,
            'type' => $type
        );
        
    }    

    public function moduleExists($name) {        
        return array_key_exists($name, $this->_modules);
    }
    
    public function getModule($name, $args=array()) {
     
        // if this module doesn't exist
        if (!$this->moduleExists($name)) { return; }     
     
        // factory or singleton
        if ($this->_modules[$name]['type'] == 'singleton') {
            if (!array_key_exists('instance', $this->_modules[$name])) {
                $v = $this->_modules[$name]['instance'] = new $this->_modules[$name]['class']($args);
            }                            
        }
        else {
            $v = new $this->_modules[$name]['class']($args);
        }     
        
        // return it        
        return $v;
        
    }

    public function getModules() {
      return $this->_modules;
    }

    public function module($name, $params=array(), $args=array()) {

        // if this module doesn't exist
        if (!$this->moduleExists($name)) { return; }
    
        // get module
        $v = $this->getModule($name, $args);

        // now render the output as a string
        return $v->setParam($params)->execute('module')->getContent();
    
    }

    public function template($tmpl, $args=array()) {    
            
        // our file name
        $file = $tmpl;
            
            // end
            if (stripos($tmpl, '.template.php') === false){ 
                $file .= '.template.php';
            }

        // vars
        $vars = p_raw('vars', array(), $args);

            // if we have a view
            if(array_key_exists('view', $args)) {                
                $vars = array_merge($vars, $args['view']->getParams()->getData());
                $vars['view'] = $args['view'];
            }

            // add our request
            $vars['request'] = b::request();

            // make sure everything is an object
            foreach ($vars as $k => $v) {
                if (is_array($v)) {
                    $vars[$k] = b::bucket($v);
                }
            }            

        // content
        $content = call_user_func(function($__my_file, $vars){

            // start ob buffer
            ob_start();

            // define all
            foreach ( $vars as $k => $v ) {
                $$k = $v;
            }       

            // include the page
            include($__my_file);

            // stop
            $page = ob_get_contents();

            // clean
            ob_clean();

            // return
            return $page;                    

        }, $file, $vars);    
        
    		// give it back
    		return $this->string($content, $args);
    
    }

    public function string($str, $args=array()) {
    
        // vars
        $vars = p_raw('vars', array(), $args);

            // if we have a view
            if(array_key_exists('view', $args)) {                
                $vars = array_merge($vars, $args['view']->getParams()->getData());
                $vars['view'] = $args['view'];
            }

            // add our request
            $vars['request'] = b::request();

            // make sure everything is an object
            foreach ($vars as $k => $v) {
                if (is_array($v)) {
                    $vars[$k] = b::bucket($v);
                }
            }            

      // what renderer
      $renderer = p('renderer', 'mustache', $args);

      // render it 
      $str = b::render($renderer)->render($str, $vars);

      // str
      return $str;

    
  //       // let's find some variables
  //       if (preg_match_all('/\{(\$[^\}]+)\}/', $str, $matches, PREG_SET_ORDER)) {
            
  //           // loop
  //           foreach ($matches as $match) {        

  //               // replace any . in the string
  //               // since they should be 
  //               $var = trim(str_replace('.', "->", $match[1]),'$');
                
  //               // tok the first str
  //               $parts = explode("->", $var);
                
  //               // first
  //               $first = array_shift($parts);
                
  //               // val
  //               $val = "";
                
  //               // value        
  //               if (array_key_exists($first, $vars)) {                
  //                   $val = eval('return '.implode('->', array_merge(array('$vars[$first]'),$parts)).";");
  //               }

  //               // if it's not a string we give up
  //               if (!is_string($val) AND !is_numeric($val)) {
  //                   $val = "";
  //               }
                
  //               // replace it 
  //               $str = preg_replace("#".preg_quote($match[0], '#')."#", $val, $str, 1);
                
  //           }
            
  //       }
        

    
  //       // first sanatize out our string
  //       $str = str_replace("\}", "%##%", $str);
        
  //       // exec
  //       $exec = $modules = array();
            	
  //   	// take the string and look for any functions
  //   	// functions always start with a b:, so that's what 
  //   	// we'll key off of
		// if ( preg_match_all("/\{(b::?[^\}]+)\}/i", $str, $matches, PREG_SET_ORDER) ) {
  //           $exec = array_merge($exec, $matches);  
  //       }

  //   	// now find direct function calls
		// if ( preg_match_all("/\{\!([^\}]+)\!\}/i", $str, $matches, PREG_SET_ORDER) ) {    
  //           $exec = array_merge($exec, $matches);  
  //       }            
    	
  //   	// now modules
		// if ( preg_match_all("/\{\%([^\}]+)\%\}/i", $str, $matches, PREG_SET_ORDER) ) {    
  //           $modules = $matches;              
  //       }
        
  //       // make sure everything is an object
  //       foreach ($vars as $k => $v) {
  //           if (is_array($v)) {
  //               $vars[$k] = new \bolt\dao\item($v);
  //           }
  //       }    
		
      
		

		// // define all
		// foreach ( $vars as $k => $v ) {
		// 	$$k = $v;
		// }		
		
		// // view
		// $view = $this;

  //       // anything to exec
  //       foreach ($exec as $stuff) {
            
  //           // some things
  //           $match = $stuff[0];            
  //           $func = $stuff[1];
                
  //           // clean the func
  //           $func = "return ".str_replace("%##%", "}", $func).";";
            
  //           // we need to buffer the output
  //           ob_start();
                
  //               // html
  //               try { $resp = eval($func); }
  //               catch (Exception $e) {}
                    
  //   		// stop
  //   		$out = ($resp !== null ? $resp : ob_get_contents());
    
  //   		// clean
  //   		ob_clean();                
            
  //           // replace our match
  //           $str = preg_replace("#".preg_quote($match, '#')."#", $out, $str, 1);		  
		
		// }    	
		
	//	var_dump($str); 
    	
    	// return a string
    	return $str;
    
    } 
    
    public function render($view, $args=array()) {
        
        // view
        $accept = p('accept', false, $args);        
        
        // execute
        $view = $view->execute(false, $accept);        
        
        
    
        // get that crap
        return $p->render($view);
        
    }

}


