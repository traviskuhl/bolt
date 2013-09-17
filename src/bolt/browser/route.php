<?php

// namespace me
namespace bolt\browser;
use \b;

// plug route & url into b
b::plug(array(
    'route' => '\bolt\browser\route',
    'url' => 'route::url'
));

// add our url helper
b::render()->once('before', function() {

    b::render()->helper('url', function($template, $context, $args, $text) {
        if (empty($args)) {return;}
        if (preg_match_all('#\$([a-zA-Z0-9_\.]+)#', $args, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $val = $context->get($match[1]);
                if (!$val AND $context->get('controller')) {
                    $val = $context->get('controller')->getParamValue($match[1]);
                }
                $args = str_replace($match[0], $val, $args);
            }
        }

        $parts = explode(" ", trim($args));
        if (count($parts) == 0) return;
        $name = trim(array_shift($parts));
        $params = array();
        $query = array();
        foreach ($parts as $part) {
            list($key, $value) = explode("=", trim($part));
            if ($key == 'query') {
                $query = json_decode($value, true);
            }
            else {
                $params[$key] = $value;
            }
        }
        return b::url($name, $params, $query);
    });
});

b::on("run", function(){
    if (b::config()->exists('compiled')) {
        b::route()->loadCompiled();
    }
});

/**
 * browser route class
 * @extends \bolt\plugin\singleton
 *
 */
class route extends \bolt\plugin\singleton {

    // type is singleton
    // since this is really a plugin dispatch
    public static $TYPE = "singleton";

    // routes
    private $_route = false;
    private $_routes = array();
    private $_defaultParser = '\bolt\browser\route\token'; // default route parser
    private $_baseUri = false;
    private $_compiled = array();

    public function __construct() {
        $this->_baseUri = new \Net_URL2((defined('bSelf') ? bSelf : ""));
    }

    /**
     * default plugin method. 0 args -> self, > 0 args register
     *
     * @params mix
     * @return mixed
     */
    public function _default() {
        if (count(func_get_args()) == 0 ) {
            return $this;
        }
        else {
            return call_user_func_array(array($this, 'register'), func_get_args());
        }
    }

    public function getBaseUri() {
        return $this->_baseUri;
    }
    public function setBaseUri($baseUri) {
        $this->_baseUri = $baseUri;
        return $this;
    }

    public function loadCompiled() {
        $file = b::path( b::config()->value("compiled"), "routes.inc");

        // if we have the
        if (file_exists($file)) {
            $routes = require($file);
            if (is_array($routes)) {
                $this->_compiled = $routes;
            }
        }

    }

    /**
     * set default route parser
     *
     * @param $class router class
     * @return self
     */
    public function setDefaultParser($class) {
        $this->_defaultParser = $class;
        return $this;
    }

    /**
     * get default route parser
     *
     * @return return default parser
     */
    public function getDefaultParser() {
        return $this->_defaultParser;
    }

    /**
     * get the current registered routes
     *
     * @return array of route objects
     */
    public function getRoutes() {
        return $this->_routes;
    }

    /**
     * get the matched route
     *
     * @return  route object
     */
    public function getRoute() {
        return $this->_route;
    }

    /**
     * get a route by name
     *
     * @param $name name of route
     * @return route object
     */
    public function getRouteByName($name) {
        foreach ($this->_routes as $route) {
            if ($route->getName() == $name) {
                return $route;
            }
        }
        return false;
    }

    /**
     * register a new route
     *
     * @param $paths (string|array) or route paths
     * @param $class controller class (default b::config->router)
     * @return router object
     */
    public function register($path, $class=false) {

        if (is_a($class, 'Closure')) {
            $tmp = new \bolt\browser\controller\closure();
            $tmp->setClosure($class);
            $class = $tmp;
        }

        // if paths is not an object
        if (!is_object($path)) {
            $cl = $this->_defaultParser;
            $route = new $cl($path, $class);
        }

        // bad parent class
        if (!in_array('bolt\browser\route\parser', class_parents($route))) {
            return false;
        }

        // add it
        return ($this->_routes[] = $route);

    }

    /**
     * match a route to a give path & method
     *
     * @param $path path string
     * @param $method requesting method
     * @return router object or false
     */
    public function match($path, $method=false) {

        // normalize the path
        $path = $opath = "/".trim($path, '/ ');

        if (count($this->_compiled) > 0) {
            foreach ($this->_compiled as $regex => $route) {
                if ($route['type']::isMatch($regex, $path)) {

                    // cool, lets load the controller
                    b::load($route['controller']);

                    var_dump( new $route['controller'] ); die;

                }
            }
        }

        // class
        $controller = false;
        $params = array();

        // sort first by length
        usort($this->_routes, function($aa, $bb) {
            $a = $aa->getWeight() * 2 + strlen($aa->getPath());
            $b = $bb->getWeight() * 2 + strlen($bb->getPath());
            if ($a == $b) {
                return 0;
            }
            return ($a > $b) ? -1 : 1;
        });


        $route = false;

        // routes
        foreach ($this->_routes as $item) {

            // method match
            if ($item->getMethod() AND !in_array($method, $item->getMethod())) {continue;}

            if ($item->getPath() == $path) {
                $route = $item;
            }
            else if (!$route AND $item->match($path) !== false) {
                $route = $item;
            }

        }


        if (!$route) {
            return false;
        }

        $controller = $route->getController();
        $params = $route->getParams();

        if ($method AND $route->getMethod() AND !in_array($method, $route->getMethod())) {
            return false;
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
        return ($this->_route = $route);

    }

    /**
     * return a url string based on named route
     *
     * @param $name route name
     * @param $data route params
     * @param $params query parameters to add
     * @param $args array of args
     * @return string url
     */
    public function url($name, $data=array(), $query=array(), $args=array()) {
        $prefix = "/";

        // if we already have url
        if (is_array($name)) {
            $base = new \Net_URL2("");
            $args = $name;
        }
        else if (substr($name, 0, 4) == 'http') {
            $base = new \Net_URL2($name);
            $base->setQueryVariables($data);
            $args = $query;
        }
        else { // no url close the base and start

            // new base
            $base = clone $this->_baseUri;

            // reset query
            if (b::param('use-base-query', false, $args) === false) {
                $base->query = false;
            }

            // are we in index.php
            if (substr($base->path, 0, 11) == '/index.php/') {
                $prefix = "/index.php/";
            }

            // no url
            if (!$this->getRouteByName($name)) {
                $base->path = $prefix . ltrim($name, '/');
                return $base->getURL();
            }

            // query
            if (is_string($query)) {
                $base->query = $query;
            }
            else {
                foreach ($query as $k => $v) {
                    $base->setQueryVariable($k, $v);
                }
            }


            // get our url
            $path = $this->getRouteByName($name)->getPath();

            foreach ($data as $k => $v) {
                if (is_string($v)) {
                    $path = str_replace('{'.$k.'}', $v, $path );
                }
            }

            // anything left over
            $path = preg_replace("#\{[^\}]+\}/?#", "", $path);

            // set it
            $base->path = $prefix . trim($path, '/');

        }

        // parts
        foreach ($args as $k => $v) {
            if ($k == 'user') {
                $base->setUserinfo($v, $base->getPassword());
            }
            else if ($k == 'pass') {
                $base->setUserinfo($base->getUser(), $v);
            }
            else {
                $m = 'set'.ucfirst($k);
                if (method_exists($base, $m)) {
                    call_user_func(array($base, $m), $v);
                }
            }
        }


        // return with params
        return $base->getURL();

    }

    /**
     * load all routes from declaired controller classes
     *
     * @return self
     */
    public function loadClassRoutes() {
        $classes = b::getDefinedSubClasses('bolt\browser\controller\request');

        // register their routes
        foreach ($classes as $class) {
            if (($class->hasProperty('routes') AND $class->getProperty('routes')->isStatic()) OR
            ($class->hasMethod('getRoutes') AND $class->getMethod('getRoutes')->isStatic())) {

                $route = array(); $dao = array();

                if ($class->hasProperty('routes')) {
                    $route = $class->getProperty('routes')->getValue();
                }
                else {
                    $method = $class->getMethod('getRoutes');
                    $route = call_user_func(array($method->class, $method->name));
                }

                // base
                $base = rtrim($class->hasProperty('routeBase') ? $class->getProperty('routeBase')->getValue() : false, '/')."/";
                $resp = rtrim($class->hasProperty('routeResponse') ? $class->getProperty('routeResponse')->getValue() : false, '/');

                if (is_string($route)) {
                    $this->register(b::path($base, $route), $class->getName());
                }
                else if (is_array($route)) {
                    if (isset($route['route']) ) {
                        $route = array($route);
                    }
                    foreach ($route as $item) {
                        $r = $this->register(b::path($base, $item['route']), $class->getName());

                        if (!array_key_exists('response', $item)) {
                            $item['response'] = $resp;
                        }
                        foreach ($item as $name => $value) {
                            if ($name != 'route') {
                                call_user_func(array($r, $name), $value);
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }

}
