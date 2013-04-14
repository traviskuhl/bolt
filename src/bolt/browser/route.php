<?php

// namespace me
namespace bolt\browser {
use \b;

// plug route
b::plug(array(
    'route' => '\bolt\browser\route',
    'url' => 'route::url'
));

// route
class route extends \bolt\plugin\singleton {

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
            $cl = (b::_('router')->value ?: '\bolt\route\token');
            $paths = new $cl($paths, $view, $method);
        }

        // add to routes
        $this->routes[] = $paths;


        return $paths;

    }

    // match
    public function match($path=false, $method=false) {

        // class
        $controller = false;
        $params = array();

        // loop through each route and
        // try to match it
        foreach ($this->routes as $route) {
            if ($route->match($path, $method) !== false) {
                $controller = $route->getController();
                $params = $route->getParams();
                break;
            }
        }

        // no view is bad
        if (!$controller AND $path != '*') {
            return $this->match('*', $method);
        }

        if (!$controller) {
            return false;
        }

        b::log('[b::route] found route %s', array($controller));

        // return what we foudn
        return array(
            'class' => $controller,
            'params' => $params
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
    private $_controller;
    private $_method = '*';
    private $_name = false;
    private $_weight = false;
    private $_validators = array();
    private $_params = array();

    final public function __construct($paths, $controller, $method='*') {
        if (!is_array($paths)) {$paths = array($paths);}
        $this->_paths = $paths;
        $this->_controller = $controller;
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
        $this->_validators[$name] = trim($regex,' ()');
        return $this;
    }

    public function getValidator($name) {
        return (array_key_exists($name, $this->_validators) ? $this->_validators[$name] : '[^\/]+');
    }

    // match a string
    abstract public function match($path, $method);

}

} // end route namespace