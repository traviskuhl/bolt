<?php

// namespace me 
namespace bolt;

// use b
use \b as b;

// plug route 
b::plug(array(
    'route' => '\bolt\route',
    'url' => 'route::url'
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
    
        // method
        $method = "*";
    
        // if paths is a string,
        // make it an array
        if (is_string($paths)) {
            
            // weight
            $w = p('weight', 0, $args);
            
            // add it
            $paths = array($w => $paths);
    
        }
        
        // figure if class is really a method
        if (is_string($class) AND in_array(strtolower($class), array("get","post","put","delete","head"))) {
            $method = strtoupper($class);
            $class = $name;
        }
    
        // now loop it up
        foreach ($paths as $weight => $path) {
        
            // ?
            if(strpos($path, '?P') !== false) {
                $this->routes[$path] = array($weight, $class, array(), $args, $method); continue;
            }
        
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
                $this->routes[$path] = array($weight, $class, $params, $args, $method);
                
            }
            
        }
        
        return true;

    }

    // match
    public function match($path=false, $method=false) {

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
        $args = array();
    
        // let's loop through 
        foreach ($this->routes as $route => $info) {         
            if (preg_match('#'.$route.'#', $path, $matches)) {
            
                // method match
                if ($info[4] != '*' AND $info[4] != $method) {
                    continue;
                }
                            
                // we don't need the match
                array_shift($matches);            
                
                // set the params
                if (isset($info[2]) AND count($info[2]) > 0) {
                    foreach ($matches as $key => $val) {
                    
                        // params
                        if (array_key_exists($key, $info[2])) {
                        
                            // set it
                            $args[$info[2][$key]] = $val;
                            
                            // is it bPath
                            if ($info[2][$key] == 'bPath') {
                                b::config()->bPath = $val;
                            }                            
                            
                        }
                    
                        
                    }
                }
                else if (strpos($route, "?P") !== false) {
                    $args = $matches;
                }
                
                // set the class
                $class = $info[1];
                
                // nope
                break;
                
            }        
        }
        
        // nothing
        if (!$class) { die; }

        // return what we foudn
        return array(
            'class' => $class,            
            'args' => $args
        ); 
    
    }
    
    // url
    public function url($name, $data=array(), $params=array(), $uri=false) {                
                
        // no url
        if (!$uri) {
            $uri = URI;
        }                

        // uri doesn't have a http://
        if (substr($uri,0,4) != 'http') {
            $uri = "http://$uri";
        }

        // not a sting
        if (!is_string($name)) { $name = (string)$name; }
                
        // no url
        if (!$name OR ($name AND !array_key_exists($name, $this->urls))) {
            return rtrim(strtolower(b::addUrlParams(rtrim($uri,'/')."/".ltrim($name,'/'), $params)),'/');
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
            $path = rtrim($uri,'/') . "/" . trim($path,'/');
        }
        
        // return with params
        return rtrim(strtolower(b::addUrlParams($path, $params)),'/');
    
    }

}