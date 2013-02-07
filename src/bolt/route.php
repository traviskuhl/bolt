<?php

// namespace me
namespace bolt {
use \b;

// plug route
b::plug(array(
    'route' => '\bolt\route',
    'url' => 'route::url'
));

// route
class route extends plugin\singleton {

    // type is singleton
    // since this is really a plugin dispatch
    public static $TYPE = "singleton";

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
    public function register($paths, $view=false, $method='*') {

        // if paths is not an object
        if (!is_object($paths)) {
            $cl = (b::_('router') ?: '\bolt\route\regex');
            $paths = new $cl($paths, $view, $method);
        }

        // add to routes
        $this->routes[] = $paths;


        // // method
        // $method = "*";

        // // if paths is a string,
        // // make it an array
        // if (is_string($paths)) {

        //     // weight
        //     $w = p('weight', 0, $args);

        //     // add it
        //     $paths = array($w => $paths);

        // }

        // // figure if class is really a method
        // if (is_string($class) AND in_array(strtolower($class), array("get","post","put","delete","head"))) {
        //     $method = strtoupper($class);
        //     $class = $name;
        // }

        // // now loop it up
        // foreach ($paths as $weight => $path) {

        //     // ?
        //     if(strpos($path, '?P') !== false) {
        //         $this->routes[$path] = array($weight, $class, array(), $args, $method); continue;
        //     }

        //     // params
        //     $params = array();

        //     // does this have name
        //     if (strpos($path, '<') !== false) {

        //         // name
        //         list($_name, $path) = explode("<", $path);

        //         // add it to the url
        //         $this->urls[$_name] = trim($path,'/$');

        //     }
        //     else if ($name) {
        //         $this->urls[$name] = trim($path,'/$?');
        //     }

        //     // if class
        //     if ($class !== false) {

        //         // matches
        //         if (preg_match_all("/\>([a-zA-Z]+)/", $path, $match, PREG_SET_ORDER)) {
        //             foreach ($match as $m) {

        //                 // take it out of the path
        //                 $path = str_replace($m[0], "", $path);

        //                 // add it to params
        //                 $params[] = $m[1];

        //             }
        //         }

        //         // add the routes
        //         $this->routes[$path] = array($weight, $class, $params, $args, $method);

        //     }

        // }

        return $paths;

    }

    // match
    public function match($path=false, $method=false) {

        // class
        $view = b::_("defaultView");
        $params = array();

        // loop through each route and
        // try to match it
        foreach ($this->routes as $route) {
            if ($route->match($path, $method) !== false) {
                $view = $route->getView();
                $params = $route->getParams();
                break;
            }
        }

        // return what we foudn
        return array(
            'class' => $view,
            'args' => $params
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

} // end bold namespace

namespace bolt\route {
use \b;

abstract class parser extends \bolt\plugin\factory {

    private $_paths;
    private $_view;
    private $_method = '*';
    private $_name = false;
    private $_weight = false;
    private $_validators = array();
    private $_params = array();

    final public function __construct($paths, $view, $method='*') {
        if (!is_array($paths)) {$paths = array($paths);}
        $this->_paths = $paths;
        $this->_view = $view;
        $this->_method = $method;
    }

    public function __call($name, $args) {
        if (substr($name,0,3) == 'set') {
            $name = strtolower(substr($name,3));
            if (property_exists($this, "_{$name}")) {
                $this->{"_$name"} = $args[0];
            }
        }
        else if (substr($name,0,3) == 'get') {
            $name = strtolower(substr($name,3));
            if (property_exists($this, "_{$name}")) {
                return $this->{"_$name"};
            }
        }
        else if (method_exists($this, $name)) {
            return call_user_func_array(array($this, $name), $args);
        }
        return $this;
    }

    public function validate($name, $regex) {
        $this->_validators[$name] = $regex;
    }

    public function getValidator($name) {
        return (array_key_exists($name, $this->_validators) ? $this->_validators[$name] : '[^\/]+');
    }

    // match a string
    abstract public function match($path, $method);

}

} // end route namespace