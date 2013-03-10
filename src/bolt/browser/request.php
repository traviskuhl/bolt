<?php

namespace bolt\browser;
use \b;

b::plug('request', '\bolt\browser\request');


class request extends \bolt\plugin\singleton {

	private $_accept = array();
	private $_method = false;
	private $_get;
	private $_post;
	private $_request;
	private $_headers;
	private $_input = "";

	public function __construct() {

		// get from env
		$this->_method = p("REQUEST_METHOD", "GET", $_SERVER);
		$this->_accept = explode(',', p('_b_accept', p('HTTP_ACCEPT', false, $_SERVER), $_GET));

		// create a bucket of our params
		$this->_get = b::bucket($_GET);
		$this->_post = b::bucket($_POST);
		$this->_request = b::bucket($_REQUEST);
		$this->_input = file_get_contents("php://input");

        // if we can get headers
        if (function_exists('getallheaders')) {
            $this->_headers = b::bucket(array_change_key_case(getallheaders()));
        }

	}

	public function run() {

        // run start
        $this->fire("run");

        // pathInfo
        $pathInfo = trim(ltrim(PATH_INFO,'/'));

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

		// is the route a clouser
		if (is_a($route['class'], 'Closure')) {

			// args
			$args = $route['params'];

            // reflect our function
            $f = new \ReflectionFunction($route['class']);

            // params
            $p = $f->getParameters();

            // yes or no
            if (count($p) > 0) {

                // do it
                $_params = array();

                // loop
                foreach ($p as $item) {
                    $_params[] = (array_key_exists($item->name, $args) ? $args[$item->name] : $item->getDefaultValue());
                }

                // call our function
                $resp = call_user_func_array($route['class'], $_params);

            }
            else {
                $resp = $route['class']();
            }

		}
		else {
			$resp = new $route['class']();
		}

        // if response isn't an object
        if (!is_object($resp)) {
            $resp = b::controller()->setContent($resp);
        }

        // it's a viewo
        if (b::isInterfaceOf($resp, '\bolt\browser\iView')) {
            $view = $resp;
            $resp = new \bolt\browser\controller();
            $resp->setContent($view->render()->getContent());
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
        $this->_params = b::bucket($route['params']);

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

            // if run is another controller
            if (b::isInterfaceOf($run, '\bolt\browser\iController') AND $run->getGuid() == $resp->getGuid()) {
                break;
            }

            // level up
            $resp = $run;

        }

        // run isn't a controller object we stop
        if (!b::isInterfaceOf($resp, '\bolt\browser\iController')) {
            return false;
        }

		// set our response and run
		return b::response()
                ->setController($resp)
                    ->run();

	}

	// shortcuts
	public function __get($name) {
		switch($name) {
			case 'params':
				return $this->getParams();
			case 'headers':
				return $this->getHeaders();
			case 'input':
				return $this->getInput();
			default:
				return false;
		};
	}

	public function getParams($type=false) {
		switch($type) {
			case 'get': return $this->_get;
			case 'post': return $this->_post;
            case 'request': return $this->_requests;
			default: return $this->_params;
		};
	}
	public function getHeaders() {
		return $this->_headers;
	}
	public function getInput() {
		return $this->_input;
	}




	public function getMethod() {
		return $this->_method;
	}
	public function setMethod($method){
		$this->_method = $method;
		return $this;
	}

	public function getAccept() {
		return $this->_accept;
	}
	public function setAccept($accept) {
		$this->_accept = (array)$accept;
		return $this;
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
