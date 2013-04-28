<?php

namespace bolt\browser;
use \b;

/// plug into b::request();
b::plug('request', '\bolt\browser\request');


////////////////////////////////////////////////////////////////////
/// @brief browser request class
/// @extends \bolt\plugin\singleton
///
////////////////////////////////////////////////////////////////////
class request extends \bolt\plugin\singleton {

	private $_accept = array();
	private $_method = false;
    private $_action = false;
	private $_get;       // $_GET
	private $_post;      // $_POST
	private $_request;   // $_REQUEST
	private $_headers;
	private $_input = "";
    private $_params = false; // route params

    ////////////////////////////////////////////////////////////////////
    /// @brief construct a new instance
    ///
    /// @return self
    ////////////////////////////////////////////////////////////////////
	public function __construct() {

		// get from env
		$this->_method = p("REQUEST_METHOD", "GET", $_SERVER);
        $a = explode(',', p('HTTP_ACCEPT', false, $_SERVER));
		$this->_accept = array_shift($a);

		// create a bucket of our params
		$this->_get = b::bucket($_GET);
		$this->_post = b::bucket($_POST);
		$this->_request = b::bucket($_REQUEST);
		$this->_input = file_get_contents("php://input");
        $this->_params = b::bucket();
        $this->_server = b::bucket(array_change_key_case($_SERVER));

        // if we can get headers
        if (function_exists('getallheaders')) {
            $this->_headers = b::bucket(array_change_key_case(getallheaders()));
        }
        else {
            $headers = array();
            foreach ($_SERVER as $name => $value) {
               if (substr($name, 0, 5) == 'HTTP_'){
                   $headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))))] = $value;
               }
           }
           $this->_headers = b::bucket($headers);
        }

	}

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC get variable. where $name is:
    ///     get -> $_GET bucket
    ///     post -> $_POST bucket
    ///     request -> $_REQUEST bucket
    ///     input -> $_input contensts
    ///     server -> $_SERVER bucket
    ///     params -> request params
    ///     headers -> headers bucket
    ///     default -> route params
    ///
    /// @param $name name of variable
    /// @return mixed
    ////////////////////////////////////////////////////////////////////
    public function __get($name) {
        switch($name) {
            case 'get':
                return $this->_get;
            case 'post':
                return $this->_post;
            case 'request':
                return $this->_request;
            case 'server':
                return $this->_server;
            case 'params':
                return $this->getParams();
            case 'headers':
                return $this->getHeaders();
            case 'input':
                return $this->getInput();
            default:
                return $this->_params->get($name);
        };
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set a route apram
    ///
    /// @param $name
    /// @param $value
    /// @return void
    ////////////////////////////////////////////////////////////////////
    public function __set($name, $value) {
        $this->_params->set($name, $value);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get request params
    ///
    /// @param $type of params. where:
    ///       get -> $_GET bucket
    ///       post -> $_POST bucket
    ///       request -> $_REQUEST bucket
    ///       default -> route params bucket
    /// @return object controller
    ////////////////////////////////////////////////////////////////////
    public function getParams($type=false) {
        switch($type) {
            case 'get': return $this->_get;
            case 'post': return $this->_post;
            case 'request': return $this->_request;
            default: return $this->_params;
        };
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set route params
    ///
    /// @param route params
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setParams(\bolt\bucket $params) {
        $this->_params = $params;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get all headers
    ///
    /// @return \bolt\bucket headers
    ////////////////////////////////////////////////////////////////////
    public function getHeaders() {
        return $this->_headers;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get input contents (php://input)
    ///
    /// @return string
    ////////////////////////////////////////////////////////////////////
    public function getInput() {
        return $this->_input;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get the route action
    ///
    /// @return string route action
    ////////////////////////////////////////////////////////////////////
    public function getAction() {
        return $this->_action;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set the route action
    ///
    /// @param $action string of route action
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setAction($action){
        $this->_action = $action;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get HTTP Method
    ///
    /// @return string of method
    ////////////////////////////////////////////////////////////////////
    public function getMethod() {
        return $this->_method;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set the HTTP method
    ///
    /// @param $method set the http method
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setMethod($method){
        $this->_method = $method;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get the accept headers
    ///
    /// @return accept header string
    ////////////////////////////////////////////////////////////////////
    public function getAccept() {
        return $this->_accept;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set the HTTP accept header
    ///
    /// @param $accept header
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setAccept($accept) {
        $this->_accept = $accept;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief execute the request
    ///
    /// @param $path request path
    /// @return executed response
    ////////////////////////////////////////////////////////////////////
	public function run($path=false) {

        // ask the router to look for classes
        b::route()->loadClassRoutes();

        // pathInfo
        $pathInfo = trim(ltrim(($path ?: $this->server->get('path_info')),'/'));

        // run start
        $this->fire("run", array('pathInfo' => $pathInfo));

		// fire lets run our router to figure out what
		// route we need to take
		$route = b::route()->match($pathInfo);

			// no route just die right now
			if (!$route) {
				return false;
			}

        // route matc
        $_route = $this->fire("routeMatch", array(
                'route' => $route
            ));

            // route is false we assume bad callback and stop
            if ($_route !== false AND $_route !== null) {
                $route = $_route;
            }

        // controller
        $reps = false;

        // call before
        $route->fire("before");

        // route class
        $class = $route->getController();
        $params = $route->getParams();

		// is the route a clouser
		if (is_a($class, 'Closure')) {

			// args
			$args = $route->getParams();

            // reflect our function
            $f = new \ReflectionFunction($class);

            // params
            $p = $f->getParameters();

            // yes or no
            if (count($p) > 0) {

                // do it
                $_params = array();

                // loop
                foreach ($p as $item) {
                    $_params[] = (array_key_exists($item->name, $args) ? $args[$item->name] : ($item->isDefaultValueAvailable() ? $item->getDefaultValue() : false));
                }

                // call our function
                $resp = call_user_func_array($class, $_params);

            }
            else {
                $resp = $class();
            }

		}
		else {
			$resp = new $class();
		}

        // if response isn't an object
        if (!is_object($resp)) {
            $resp = b::controller()->setContent($resp);
        }

        // it's a viewo
        if (b::isInterfaceOf($resp, '\bolt\browser\iView')) {
            $view = $resp;
            $resp = new \bolt\browser\controller();
            $resp->setContent($view->render());
        }

        // if response isn't a controller interface
        // we need to stop
        if (!b::isInterfaceOf($resp, '\bolt\browser\iController')) {
            b::log("request run response is not an interface of iController");
            return false;
        }

        $this->fire("beforeControllerRun", array(
                'controller' => $resp,
                'route' => $route
            ));

        // request params
        $this->_params = b::bucket($params);

        // our aciton
        $this->_action = $route->getAction();

        // no never ending loops
        $i = 0;

        // run our controller and see what comes back
        while ($i++ < 10) {

            // run the response
            $run = $resp->run();

            // if run is a falsy value
            // we can stop now
            if (!$run) { break; }

            // if run isn't an object we stop
            if (!is_object($run)) { break; }

            // it's a viewo
            if (b::isInterfaceOf($run, '\bolt\browser\iView')) {
                $resp->setContent($resp->render($run));
                break;
            }

            // if run is another controller
            else if (b::isInterfaceOf($run, '\bolt\browser\iController') AND $run->getGuid() == $resp->getGuid()) {
                break;
            }

            // level up
            $resp = $run;

        }

        // run isn't a controller object we stop
        if (!b::isInterfaceOf($resp, '\bolt\browser\iController')) {
            return false;
        }

        // set the controller
        b::response()->setController($resp);

        // call before
        $route->fire("after");

		// set our response and run
		return $this;

	}

    public static function initServer() {
        if (defined('bServerInit') AND bServerInit === true) {return;}

        // always need from server
        $needed = array(
            'SERVER_PORT', 'HTTP_HOST', 'REMOTE_ADDR', 'QUERY_STRING', 'REQUEST_URI', 'SCRIPT_NAME', 'PATH_INFO'
        );

        foreach ($needed as $name) {
            if (!isset($_SERVER[$name])) {
                $_SERVER[$name] = "";
            }
        }

        // forward
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
        }

        // forward
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) AND $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $_SERVER['SERVER_PORT'] = 443;
        }

        if (isset($_SERVER['HTTP_X_PORT'])) {
            $_SERVER['SERVER_PORT'] = $_SERVER['HTTP_X_PORT'];
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
            $_SERVER['SERVER_PORT'] = $_SERVER['HTTP_X_FORWARDED_PORT'];
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_SERVER'])) {
            $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_X_FORWARDED_SERVER'];
        }

        // make sure host has a port if it's non-standard
        if (!in_array($_SERVER['SERVER_PORT'],array(80,443)) AND stripos($_SERVER['HTTP_HOST'], ':') === false) {
            $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'].":".$_SERVER['SERVER_PORT'];
        }

        // , means it's ben forwarded
        if (stripos($_SERVER['REMOTE_ADDR'], ',') !== false) {
            $_SERVER['REMOTE_ADDR'] = trim(array_shift(explode(',', $_SERVER['REMOTE_ADDR'])));
        }

        // get the file name
        $path = explode("/",$_SERVER['SCRIPT_FILENAME']);

        // need to get base tree
        $uri = explode('/',$_SERVER['SCRIPT_NAME']);
        $hostParts = explode(":", $_SERVER['HTTP_HOST']);

        define("HTTP_HOST",      $_SERVER['HTTP_HOST']);
        define("HOST",           ($_SERVER['SERVER_PORT']==443?"https://":"http://").$_SERVER['HTTP_HOST']);
        define("HOST_NSSL",      "http://".$_SERVER['HTTP_HOST']);
        define("HOST_SSL",       "https://".$_SERVER['HTTP_HOST']);
        define("HOSTNAME",         array_shift($hostParts));

        if (rtrim(str_replace("?".$_SERVER['QUERY_STRING'], "", $_SERVER['REQUEST_URI']),'/') == $_SERVER['SCRIPT_NAME']) {
            define("URI",            HOST.implode("/",$uri)."/");
            define("URI_NSSL",       HOST_NSSL.implode("/",$uri)."/");
            define("URI_SSL",        HOST_SSL.implode("/",$uri)."/");
        }
        else {
            define("URI",            HOST.implode("/",array_slice($uri,0,-1))."/");
            define("URI_NSSL",       HOST_NSSL.implode("/",array_slice($uri,0,-1))."/");
            define("URI_SSL",        HOST_SSL.implode("/",array_slice($uri,0,-1))."/");
        }

        if (isset($_SERVER['PATH_INFO'])) {
            define("PATH_INFO", $_SERVER['PATH_INFO']);
        }
        else {
            define("PATH_INFO", str_replace("?".$_SERVER['QUERY_STRING'], "", $_SERVER['REQUEST_URI']));
        }

        define("COOKIE_DOMAIN",  false);
        define("IP",             $_SERVER['REMOTE_ADDR']);
        define("SELF",           HOST.$_SERVER['REQUEST_URI']);
        define("PORT",           $_SERVER['SERVER_PORT']);
        define("PROTO",         (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : 'http'));
        define("bServerInit",   true);


    }

}

