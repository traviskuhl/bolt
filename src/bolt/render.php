<?php

namespace bolt;

use \b as b;

// render
b::plug(array(
    'render' => '\bolt\render',
    'template' => 'render::template'
));

class render extends plugin {

    // factory
    public static $TYPE = 'singleton';

    // global args
    private $_globals = array();

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

    public function template($tmpl, $args=array(), $view=false) {    
    
        // vars
        $vars = (isset($args['vars']) ? $args['vars'] : array());
    
        // get, post, request
        $vars['_get'] = $_GET;
        $vars['_post'] = $_POST;
        $vars['_request'] = $_REQUEST;
        $vars['_server'] = $_SERVER;    
    
        // our file name
        $x__file = $tmpl;
            
            // end
            if (stripos($tmpl, '.template.php') === false){ 
                $x__file .= '.template.php';
            }

		// get any globals
		$vars = array_merge( $this->_globals, $view->getParams(), $vars );

            // make sure everything is an object
            foreach ($vars as $k => $v) {
                if (is_array($v)) {
                    $vars[$k] = new \bolt\dao\item(array(), $v);
                }
            }
        
		// start ob buffer
		ob_start();

		// define all
		foreach ( $vars as $k => $v ) {
			$$k = $v;
		}

		// include the page
		include $x__file;

		// stop
		$page = ob_get_contents();

		// clean
		ob_clean();

		// give it back
		return $this->string($page, $vars, $view);
    
    }

    public function string($str, $vars=array(), $view=false) {
    
        // get, post, request
        $vars['_get'] = $_GET;
        $vars['_post'] = $_POST;
        $vars['_request'] = $_REQUEST;
        $vars['_server'] = $_SERVER;
    
        // let's find some variables
        if (preg_match_all('/\{(\$[^\}]+)\}/', $str, $matches, PREG_SET_ORDER)) {
            
            // loop
            foreach ($matches as $match) {        

                // replace any . in the string
                // since they should be 
                $var = trim(str_replace('.', "->", $match[1]),'$');
                
                // tok the first str
                $parts = explode("->", $var);
                
                // first
                $first = array_shift($parts);
                
                // val
                $val = "";
                
                // value        
                if (array_key_exists($first, $vars)) {                
                    $val = eval('return '.implode('->', array_merge(array('$vars[$first]'),$parts)).";");
                }

                // if it's not a string we give up
                if (!is_string($val) AND !is_numeric($val)) {
                    $val = "";
                }
                
                // replace it 
                $str = preg_replace("#".preg_quote($match[0], '#')."#", $val, $str, 1);
                
            }
            
        }
    
        // first sanatize out our string
        $str = str_replace("\}", "%##%", $str);
        
        // exec
        $exec = array();
            	
    	// take the string and look for any functions
    	// functions always start with a b:, so that's what 
    	// we'll key off of
		if ( preg_match_all("/\{(b::?[^\}]+)\}/i", $str, $matches, PREG_SET_ORDER) ) {
            $exec = array_merge($exec, $matches);  
        }
            
    	
    	// now find direct function calls
		if ( preg_match_all("/\{\%([^\}]+)\%\}/i", $str, $matches, PREG_SET_ORDER) ) {    
            $exec = array_merge($exec, $matches);  
        }
        
        // make sure everything is an object
        foreach ($vars as $k => $v) {
            if (is_array($v)) {
                $vars[$k] = new \bolt\dao\item(array(), $v);
            }
        }        

		// define all
		foreach ( $vars as $k => $v ) {
			$$k = $v;
		}

        // anything to exec
        foreach ($exec as $stuff) {
            
            // some things
            $match = $stuff[0];            
            $func = $stuff[1];
                
            // clean the func
            $func = "return ".str_replace("%##%", "}", $func).";";
            
            // we need to buffer the output
            ob_start();
                
                // html
                try { $resp = eval($func); }
                catch (Exception $e) {}
                    
    		// stop
    		$out = ($resp !== null ? $resp : ob_get_contents());
    
    		// clean
    		ob_clean();                
            
            // replace our match
            $str = preg_replace("#".preg_quote($match, '#')."#", $out, $str, 1);		  
		
		}    	
    	
    	// return a string
    	return $str;
    
    } 
    
    public function render($view, $args=array()) {
        
        // view
        $method = strtolower(p('method', 'get', $args));
        $accept = p('accept', 'text/html', $args);        
        
        // figure out if the requested accept
        // is allowed in the view
        if (!in_array($accept, $view->getAccept()) AND !in_array('*/*', $view->getAccept())) {
            $accept = array_shift($view->getAccept());
        }

            
        // if our accept header says it's ajax
        if ($accept == 'text/javascript;text/ajax' AND method_exists($view, 'ajax')) {
            $view->ajax();
        }        

        // does this method exist for this objet        
        if (method_exists($view, $method)) {  
            $view->$method();
        }            
        
        // there's a dispatch
        else if (method_exists($view, '_dispatch')) { 
            $view->_dispatch();            
        }
        
        // a get to fall back on 
        else if (method_exists($view, 'get')) {
            $view->get();
        }
        
        // wrap
        if (isset($args['wrap']) AND $args['wrap'] AND $view->getWrap() === -1) {
            $view->setWrap($this->template($args['wrap'], $args, $view));
        }
        else if (stripos($view->getWrap(), '.template.php') !== false) { 
            $view->setWrap($this->template($view->getWrap(), $args, $view));
        }
                        
        // accept
        $map = array();
        
        // loop through all our plugins 
        // to figure out which render to use 
        foreach ($this->getPlugins() as $plug => $class) {        
            foreach ($class::$accept as $weight => $str) {
                $map[] = array($weight, $str, $plug);
            }
        }
        
        // sort renders by weight
        uasort($map, function($a,$b){
            if ($a[0] == $b[0]) {
                return 0;
            }
            return ($a[0] < $b[0]) ? -1 : 1;        
        }); 
        
        // plug
        $plug = "html";  
        
        // loop it 
        foreach ($map as $item) {
            if ($item[1] == $accept) {
                $plug = $item[2]; break;
            }
        }
        
        // get our 
        $p = $this->call($plug);

        // what do they want back
        header("Content-Type:{$p->contentType}", false, $view->getStatus());
    
        // headers
        foreach (array_merge($view->getHeaders(), $view->getHeaders()) as $name => $value) {
            header("$name: $value");
        }
    
        // get that crap
        return $p->render($view);
        
    }

}


