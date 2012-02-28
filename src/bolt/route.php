<?php

// namespace me 
namespace bolt;

// use b
use \b as b;

// plug route 
b::plug(array(
    'route' => '\bolt\route',
    'url' => 'route::url',
    'run' => 'route::execute'
));

// route
class route extends plugin\singleton {
    
    // routes
    private $routes = array();
    private $urls = array();

    // default
    public function _default() {
        if (count(func_get_args()) == 0 ) {
            return $this;
        }
        else {
            return call_user_func_array(array($this, 'register'), func_get_args());
        }
    }
    
    // get routes
    public function getRoutes() {
        return $this->routes;
    }

    // register
    public function register($paths, $class=false, $name=false, $args=array()) {
    
        // if paths is a string,
        // make it an array
        if (is_string($paths)) {
        
            // weight
            $w = p('weight', 0, $args);
            
            // add it 
            $paths = array($w => $paths);
            
        }
    
        // now loop it up
        foreach ($paths as $weight => $path) {
        
            // params
            $params = array();
            
            // does this have name
            if (strpos($path, '<') !== false) {
            
                // name
                list($_name, $path) = explode("<", $path);
                
                // add it to the url
                $this->urls[$_name] = trim($path,'/$');
                
            }
            else if ($name) {
                $this->urls[$name] = trim($path,'/$?');
            }
        
            // if class
            if ($class !== false) {
            
                // matches
                if (preg_match_all("/\>([a-zA-Z]+)/", $path, $match, PREG_SET_ORDER)) {
                    foreach ($match as $m) {
                        
                        // take it out of the path
                        $path = str_replace($m[0], "", $path);
                    
                        // add it to params
                        $params[] = $m[1];
                        
                    }
                }
                    
                // add the routes
                $this->routes[$path] = array($weight, $class, $params, $args);
                
            }
            
        }
        
        return true;

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
        $args = array();
    
        // let's loop through 
        foreach ($this->routes as $route => $info) {        
            if (preg_match('#'.$route.'#', $path, $matches)) {
                
                // we don't need the match
                array_shift($matches);
                
                // params
                $params = array();               
                
                // set the params
                if (isset($info[2]) AND count($info[2]) > 0) {
                    foreach ($matches as $key => $val) {
                    
                        // params
                        $params[$info[2][$key]] = $val;
                        
                        // is it bPath
                        if ($info[2][$key] == 'bPath') {
                            b::config()->bPath = $val;
                        }
                        
                    }
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
            'params' => $params,
            'args' => $args
        ); 
    
    }
    
    // run
    public function execute($args=array()) {
        
        // some default params
        $path = p('path', bPath, $args);
        $method = p('method', p("REQUEST_METHOD", "GET", $_SERVER), $args); 
        $accept = p('accept', p('_accept', p('HTTP_ACCEPT', false, $_SERVER)), $args);            
            
        // get our class
        $route = $this->match(bPath);
        
        // define
        $class = $route['class'];
        $params = $route['params'];
        
        // method
        $m = strtolower($method);        
    
        // call our class
        $view = new $class($params, $method);  
                
        // render me 
        exit(b::render()->render($view, array( 
            'accept' => $accept,
            'wrap' => b::config()->wrapTemplate
        )));
            
    }
    
    // url
    public function url($name, $data=array(), $params=array(), $uri=false) {                
                
        // no url
        if (!$uri) {
            $uri = URI;
        }                
                
        // no url
        if (!array_key_exists($name, $this->urls)) {
            return rtrim($uri,'/')."/".ltrim($name,'/');
        }
    
        // get our url
        $url = $this->urls[$name];
        
        // get our parts
        $parts = explode("/", stripslashes($url));
        
        // lets do it
        foreach ($parts as $i => $part) {
        
            // does this part have a :
            if (strpos($part, '>')!== false) {
            
                // loop through our data
                foreach ($data as $k => $v) {                
                    if (stripos($part, ">$k") !== false) {
                        $parts[$i] = $v; goto forward;
                    }
                }
                
                // unset this one
                unset($parts[$i]);
                
            }
            
            // we end here
            forward:
                
        }
        
        // path
        $path = implode("/", $parts);
        
        // base url
        if (stripos($path, 'http') == false) {
            $path = rtrim($uri,'/') . "/" . ltrim($path,'/');
        }
        
        // return with params
        return b::addUrlParams($path, $params);
    
    }

}