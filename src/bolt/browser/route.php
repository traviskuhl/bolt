<?php

// namespace me
namespace bolt\browser;
use \b;

// plug route & url into b
b::plug(array(
    'route' => '\bolt\browser\route',
    'url' => 'route::url'
));


////////////////////////////////////////////////////////////////////
/// @brief browser route class
/// @extends \bolt\plugin\singleton
///
////////////////////////////////////////////////////////////////////
class route extends \bolt\plugin\singleton {

    // type is singleton
    // since this is really a plugin dispatch
    public static $TYPE = "singleton";

    // routes
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

    ////////////////////////////////////////////////////////////////////
    /// @brief default plugin method. 0 args -> self, > 0 args register
    ///
    /// @params mix
    /// @return mixed
    ////////////////////////////////////////////////////////////////////
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

    ////////////////////////////////////////////////////////////////////
    /// @brief set default route parser
    ///
    /// @param $class router class
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setDefaultParser($class) {
        $this->_defaultParser = $class;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get default route parser
    ///
    /// @return return default parser
    ////////////////////////////////////////////////////////////////////
    public function getDefaultParser() {
        return $this->_defaultParser;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get the current registered routes
    ///
    /// @return array of route objects
    ////////////////////////////////////////////////////////////////////
    public function getRoutes() {
        return $this->_routes;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get a route by name
    ///
    /// @param $name name of route
    /// @return route object
    ////////////////////////////////////////////////////////////////////
    public function getRouteByName($name) {
        foreach ($this->_routes as $route) {
            if ($route->getName() == $name) {
                return $route;
            }
        }
        return false;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief register a new route
    ///
    /// @param $paths (string|array) or route paths
    /// @param $class controller class (default b::config->router)
    /// @return router object
    ////////////////////////////////////////////////////////////////////
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

    ////////////////////////////////////////////////////////////////////
    /// @brief match a route to a give path & method
    ///
    /// @param $path path string
    /// @param $method requesting method
    /// @return router object or false
    ////////////////////////////////////////////////////////////////////
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
        return $route;

    }

    ////////////////////////////////////////////////////////////////////
    /// @brief return a url string based on named route
    ///
    /// @param $name route name
    /// @param $data route params
    /// @param $params query parameters to add
    /// @param $args array of args
    /// @return string url
    ////////////////////////////////////////////////////////////////////
    public function url($name, $data=array(), $query=array(), $args=array()) {

        if (substr($name, 0, 4) == 'http') {
            return b::addUrlParams($name, $data);
        }

        // short cut
        $args['query'] = \http_build_str($query);

        $parts = $this->_baseUri;

        // no url
        if (!$this->getRouteByName($name)) {
            $parts['path'] = $name;
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

        $parts['path'] = $path;

        // return with params
        return \http_build_url(false, $parts);

    }

    ////////////////////////////////////////////////////////////////////
    /// @brief load all routes from declaired controller classes
    ///
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function loadClassRoutes() {
        $classes = array();

        // get the files we've loaded
        foreach (get_declared_classes() as $class) {
            $c = new \ReflectionClass($class);
            if (
                $c->isSubclassOf('bolt\browser\controller') AND
                (
                    ($c->hasProperty('routes') AND $c->getProperty('routes')->isStatic()) OR
                    ($c->hasMethod('getRoutes') AND $c->getMethod('getRoutes')->isStatic())
                )
            ) {
                $classes[] = $c;
            }

        }

        // register their routes
        foreach ($classes as $class) {
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

        return $this;
    }

}
