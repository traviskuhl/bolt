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
    b::render()->handlebars->helper('url', function($template, $context, $args, $text) {
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
        $parts = explode(",", trim($args));
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
    private $_baseUri = array(
            'scheme' => false,
            'host' => false,
            'port' => false
        );

    public function __construct() {
        if (defined('SELF')) {
            foreach (parse_url(SELF) as $k => $v) {
                if (array_key_exists($k, $this->_baseUri)) {
                    $this->_baseUri[$k] = $v;
                }
            }
        }
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
    public function register($paths, $class=false) {

        // if paths is not an object
        if (!is_object($paths)) {
            $cl = $this->_defaultParser;
            $paths = new $cl($paths, $class);
        }

        // bad parent class
        if (!in_array('bolt\browser\route\parser', class_parents($paths))) {
            return false;
        }

        // add it
        return ($this->_routes[] = $paths);

    }

    /**
     * match a route to a give path & method
     *
     * @param $path path string
     * @param $method requesting method
     * @return router object or false
     */
    public function match($path, $method=false) {

        // class
        $controller = false;
        $params = array();

        // loop through each route and
        // try to match it
        foreach ($this->_routes as $route) {
            if ($route->match($path) !== false) {
                $controller = $route->getController();
                $params = $route->getParams();
                break;
            }
        }

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

        if (substr($name, 0, 4) == 'http') {
            return b::addUrlParams($name, $data);
        }

        // short cut
        $args['query'] = \http_build_str($query);

        $parts = $this->_baseUri;

        // are we in index.php
        if (substr(parse_url(SELF, PHP_URL_PATH), 0, 11) == '/index.php/') {
            $prefix = "/index.php/";
        }

        // no url
        if (!$this->getRouteByName($name)) {
            $parts['path'] = $prefix . ltrim($name, '/');
            return \http_build_url(false, $parts);
        }

        foreach ($args as $k => $v) {
            $parts[$k] = $v;
        }

        // get our url
        $path = $this->getRouteByName($name)->getPath();

        foreach ($data as $k => $v) {
            $path = str_replace('{'.$k.'}', $v, $path );
        }

        $parts['path'] = $prefix . ltrim($path, '/');

        // return with params
        return \http_build_url(false, $parts);

    }

    /**
     * load all routes from declaired controller classes
     *
     * @return self
     */
    public function loadClassRoutes() {
        $classes = b::getDefinedSubClasses('bolt\browser\controller');

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

                // dao
                if ($class->hasProperty('dao') AND $class->getProperty("dao")->isStatic()) {
                    $dao = $class->getProperty('dao')->getValue();
                }

                if (is_string($route)) {
                    $this->register($route, $class->getName());
                }
                else if (is_array($route)) {
                    if (isset($route['route']) ) {
                        $route = array($route);
                    }
                    foreach ($route as $item) {
                        $r = $this->register($item['route'], $class->getName());
                        if (count($dao)) {
                            $r->dao($dao);
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
