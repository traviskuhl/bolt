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

// run
b::plug('run', function(){
    return b::route()->execute();
});

// route
class route extends \bolt\singleton {
    
    // routes
    private $routes = array();
    private $urls = array();

    // default
    public function _default() {
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
    public function match($path=false) {
        
        // sort routes by weight
        uasort($this->routes, function($a,$b){
            if ($a[0] == $b[0]) {
                return 0;
            }
            return ($a[0] < $b[0]) ? -1 : 1;        
        });
        
        // class
        $class = b::_("defaultView");
        
        // params
        $params = array();
    
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
                
                // set the class
                $class = $info[1];
                
                // nope
                break;
                
            }        
        }

        // return what we foudn
        return array(
            'class' => $class,
            'params' => $params
        ); 
    
    }
    
    // run
    public function execute() {
            
        // get our class
        $route = $this->match(bPath);
        
        // define
        $class = $route['class'];
        $params = $route['params'];
    
        // method
        $method = p("HTTP_METHOD", "GET", $_SERVER);
        
        // method
        $m = strtolower($method);        
    
        // call our class
        $o = new $class($params, $method);
        
        // ajax and accept
        $ajax = p('_ajax', p("HTTP_X_AJAX", false, $_SERVER));
        $accept = p('_accept', p('HTTP_ACCEPT', false, $_SERVER));
        
        // does this method exist for this objet
        if (method_exists($o, $m)) {
            $o->$m();
        }
        
        // if our accept header says it's ajax
        else if ($ajax AND method_exists($o, 'ajax')) {
            $o->ajax();
        }
        
        // there's a dispatch
        else if (method_exists($o, 'dispatch')) {
            $o->dispatch();
        }
        
        // a get to fall back on 
        else if (method_exists($o, 'get')) {
            $o->get();
        }      

        // what do they want back
        header("Content-Type:text/html", false, $o->getStatus());
    
        // headers
        foreach ($o->getHeaders() as $name => $value) {
            header("$name: $value");
        }
    
        // what do they want back
        if ($accept == 'text/javascript') {
        
            // the header we want
            header("Content-Type: text/javascript", true, $o->getStatus());
        
            // if it's ajax just print the data
            if ($ajax) {
                exit(json_encode(array('status'=>$o->getStatus(), 'response' => $o->getData() )));    
            }
            else {
                exit(json_encode(array('status'=>$o->getStatus(), 'html' => $o->getContent(), 'data' => $o->getData() )));            
            }            
            
        }
        else {
            exit($o->getContent());
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