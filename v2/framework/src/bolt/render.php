<?php

namespace bolt;

use \b as b;

// render
b::plug('render', '\bolt\render');

// template shortcut
b::plug("template", function(){
    return call_user_func_array(array(b::render(), 'template'), func_get_args());
});

class render extends singleton {

    // global args
    private $_globals = array();

    // default
    public function _default($args) {
    
        // render a template
        if (isset($args['template'])) {
            $tmpl = $args['template']; unset($args['template']);
            return $this->template($tmpl, $args);
        }
        
        // render a string
        else if (isset($args['string'])) {
            $str = $args['string']; unset($args['string']);        
            return $this->string($str, $args);
        }
        
        // render a module
        else if (isset($args['module'])) {
            $mod = $args['module']; unset($args['module']);                
            return $this->module($mod, $args);
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

    public function template($tmpl, $args=array()) {
    
        // vars
        $vars = (isset($args['vars']) ? $args['vars'] : array());
    
        // our file name
        $x__file = $tmpl;
            
            // end
            if (stripos($tmpl, '.template.php') === false){ 
                $x__file .= '.template.php';
            }

		// get any globals
		$vars = array_merge( $this->_globals, $vars );

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
		return $this->string($page, $vars);
    
    }

    public function string($str, $vars=array()) {
    
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
                if (!is_string($val)) {
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
            foreach ($matches as $match) {             
                $exec[] = $match;                
            }            
        }
            
    	
    	// now find direct function calls
		if ( preg_match_all("/\{\%([^\}]+)\%\}/i", $str, $matches, PREG_SET_ORDER) ) {
            foreach ($matches as $match ) {
                $exec[] = $match;                          
            }
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

}


