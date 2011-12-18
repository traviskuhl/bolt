<?php

// namespace me 
namespace bolt;

// use b
use \b as b;

// plug
b::plug('route', '\bolt\route');

// route
class route extends \bolt\singleton {
    
    // routes
    private $routes = array();

    // default
    public function __default() {
        $this->register(func_get_arg(0), func_get_arg(1));   
    }

    // register
    public function register($paths, $class) {
    
        // if paths is a string,
        // make it an array
        if (is_string($paths)) {
            $paths = array($paths);
        }
    
        // now loop it up
        foreach ($paths as $weight => $path) {
        
            // params
            $params = array();
        
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

}