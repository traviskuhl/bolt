<?php

// namespace me 
namespace bolt;

// use b
use \b as b;

// plug route 
b::plug('route', '\bolt\route');

// plugin url
b::plug('url', function(){
    return call_user_func_array(array(b::route(), 'url'), func_get_args());
});

// route
class route extends \bolt\singleton {
    
    // routes
    private $routes = array();
    private $urls = array();

    // default
    public function __default() {
        return call_user_func_array(array($this, 'register'), func_get_args());
    }

    // register
    public function register($paths, $class=false, $name=false) {
    
        // if paths is a string,
        // make it an array
        if (is_string($paths)) {
            $paths = array($paths);
        }
    
        // now loop it up
        foreach ($paths as $weight => $path) {
        
            // params
            $params = array();
            
            // does this have name
            if (strpos($path, '@') !== false) {
            
                // name
                list($_name, $path) = explode("@", $path);
                
                // add it to the url
                $this->urls[$_name] = $path;
                
            }
            else if ($name) {
                $this->urls[$name] = $path;
            }
        
            // if class
            if ($class !== false) {
            
                // matches
                if (preg_match_all("/\:([a-zA-Z]+)/", $path, $match, PREG_SET_ORDER)) {
                    foreach ($match as $m) {
                        
                        // take it out of the path
                        $path = str_replace($m[0], "", $path);
                    
                        // add it to params
                        $params[] = $m[1];
                        
                    }
                }
                    
                // add the routes
                $this->routes[$path] = array($weight, $class, $params);
                
            }
            
        }
    
    }

    // match
    public function match($path, $method="GET") {
        
        // valid methods
        $methods = array("GET", "POST", "PUT", "DELETE", "HEAD");
    
        // method
        if (!in_array($method, $methods)) { $method = p("HTTP_METHOD", "GET", $_SERVER); }    
        
        // method
        $m = strtolower($method);
        
        // sort routes by weight
        uasort($this->routes, function($a,$b){
            if ($a[0] == $b[0]) {
                return 0;
            }
            return ($a[0] < $b[0]) ? -1 : 1;        
        });
    
        // let's loop through 
        foreach ($this->routes as $route => $info) {        
            if (preg_match('#'.$route.'#', $path, $matches)) {
                
                // we don't need the match
                array_shift($matches);
                
                // params
                $params = array();
                
                // set the params
                foreach ($matches as $key => $val) {
                    $params[$info[2][$key]] = $val;
                }
                
                // call our class
                $o = new $info[1]($params);
                
                // does this method exist for this objet
                if (method_exists($o, $m)) {
                    return $o->$m();
                }
                else {
                    return $o->get();
                }
                
            }        
        }
    
    }
    
    // url
    public function url($name, $data=array(), $params=array(), $uri=false) {
                
        // no url
        if (!array_key_exists($name, $this->urls)) {
            return $name;
        }
    
        // get our url
        $url = $this->urls[$name];
        
        // get our parts
        $parts = explode("/", $url);
        
        // lets do it
        foreach ($parts as $i => $part) {
        
            // does this part have a :
            if (strpos($part, ':')!== false) {
            
                // loop through our data
                foreach ($data as $k => $v) {                
                    if (stripos($part, ":$k") !== false) {
                        $parts[$i] = $v; goto forward;
                    }
                }
                
                // unset this one
                unset($parts[$i]);
                
            }
            
            // we end here
            forward:
                
        }
        
        return implode("/", $parts);
        
    
    }

}