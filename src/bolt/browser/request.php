<?php

namespace bolt\browser;
use \b;

// plug into b::request();
b::depend('bolt-browser-*')->plug('request', '\bolt\browser\request');

/**
 * browser request class
 * @extends \bolt\plugin\singleton
 *
 */
class request extends \bolt\plugin\singleton {

	private $_accept = "text/html";
	private $_method = false;
    private $_action = false;
	private $_get;       // $_GET
	private $_post;      // $_POST
	private $_request;   // $_REQUEST
	private $_headers;
	private $_input = "";
    private $_params = false; // route params
    private $_route = false;
    private $_content = false;
    private $_data = array();
    private $_responseType = "html";

    /**
     * construct a new instance
     *
     * @return self
     */
	public function __construct() {

		// get from env
		$this->_method = b::param("REQUEST_METHOD", "GET", $_SERVER);
        $a = explode(',', b::param('HTTP_ACCEPT', false, $_SERVER));
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
           $this->_headers = b::bucket($headers, false);
        }

        // when we run
        b::on('run', function(){

            // register settings
            // register a global rendering value
            b::render()
                ->variable('settings', array('project' => b::getSettings('project')))
                ->variable('config', array('project' => b::config()->project, 'global' => b::config()->global))
                ->variable('env', b::env());

        });

	}


    /**
     * MAGIC get variable. where $name is:
     *     get -> $_GET bucket
     *     post -> $_POST bucket
     *     request -> $_REQUEST bucket
     *     input -> $_input contensts
     *     server -> $_SERVER bucket
     *     params -> request params
     *     headers -> headers bucket
     *     default -> route params
     *
     * @param $name name of variable
     * @return mixed
     */
    public function __get($name) {
        switch($name) {
            case 'qs':
            case 'query':
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

    /**
     * set a route apram
     *
     * @param $name
     * @param $value
     * @return void
     */
    public function __set($name, $value) {
        $this->_params->set($name, $value);
        return $this;
    }

    public function getRoute() {
        return $this->_route;
    }

    /**
     * get request params
     *
     * @param $type of params. where:
     *       get -> $_GET bucket
     *       post -> $_POST bucket
     *       request -> $_REQUEST bucket
     *       default -> route params bucket
     * @return object controller
     */
    public function getParams($type=false) {
        switch($type) {
            case 'get': return $this->_get;
            case 'post': return $this->_post;
            case 'request': return $this->_request;
            default: return $this->_params;
        };
    }

    /**
     * set route params
     *
     * @param route params
     * @return self
     */
    public function setParams($params) {
        $this->_params = $params;
        return $this;
    }

    /**
     * get all headers
     *
     * @return \bolt\bucket headers
     */
    public function getHeaders() {
        return $this->_headers;
    }

    /**
     * get input contents (php://input)
     *
     * @return string
     */
    public function getInput() {
        return $this->_input;
    }

    /**
     * get the route action
     *
     * @return string route action
     */
    public function getAction() {
        return $this->_action;
    }

    /**
     * set the route action
     *
     * @param $action string of route action
     * @return self
     */
    public function setAction($action){
        $this->_action = $action;
        return $this;
    }

    /**
     * get HTTP Method
     *
     * @return string of method
     */
    public function getMethod() {
        return $this->_method;
    }

    /**
     * set the HTTP method
     *
     * @param $method set the http method
     * @return self
     */
    public function setMethod($method){
        $this->_method = $method;
        return $this;
    }

    /**
     * get the accept headers
     *
     * @return accept header string
     */
    public function getAccept() {
        return $this->_accept;
    }

    /**
     * set the HTTP accept header
     *
     * @param $accept header
     * @return self
     */
    public function setAccept($accept) {
        $this->_accept = $accept;
        return $this;
    }

    public function getContent() {
        return $this->_content;
    }

    public function setData($data) {
        $this->_data = $data;
        return $this;
    }

    public function getData() {
        return $this->_data;
    }

    public function getResponseType() {
        return $this->_responseType;
    }

    /**
     * execute the request
     *
     * @param $path request path
     * @return executed response
     */
	public function run($path=false, $method=false) {

        // ask the router to look for classes
        b::route()->loadClassRoutes();

        // pathInfo
        $pathInfo = trim(ltrim(($path ?: bPathInfo),'/'));
        $method = strtolower($method ?: $this->getMethod());

        // run start
        $this->fire("run", array('pathInfo' => $pathInfo));

		// fire lets run our router to figure out what
		// route we need to take
		$route = b::route()->match($pathInfo, $method);


		// no route just die right now
		if (!$route) {
			$controller = b::browser()->error('404', "No route for path '".strtoupper($method)." {$pathInfo}'");
		}
        else  {

            // route matc
            $_route = $this->fire("routeMatch", array(
                    'route' => $route
                ));

                // route is false we assume bad callback and stop
                if ($_route !== false AND $_route !== null) {
                    $route = $_route;
                }

            // globalize route
            $this->_route = $route;

            // call before
            $route->fire("before", array(
                'route' => $route
            ));

            // our aciton
            $this->_action = $route->getAction();

            // class
            $class = $route->getController();

            // rew controller
            $controller = (is_string($class) ? new $class() : $class);

            $controller->setRoute($route);

            // route
            $this->_responseType = $route->getResponseType();

        }

        // request before
        $this->fire("before", array(
            'route' => $route,
            'controller' => $controller
        ));

        // run our controller
        $controller = $controller
                        ->setMethod($method)
                        ->render();

        // set the controller
        $this->_content = $controller->getContent($this->_responseType);
        $this->_data = $controller->getData();

        $_args = array(
            'controller' => $controller,
            'request' => $this
        );

        // after request is finished
        $this->fire("after", $_args);

        // call before
        if ($route) { $route->fire("after", $_args); }

		// set our response and run
		return $this;

	}

    public static function initGlobals() {
        if (defined('bServerInit') AND bServerInit === true) {return;}

        // always need from server
        $needed = array(
            'SERVER_PORT', 'HTTP_HOST', 'HTTP_PROTO', 'REMOTE_ADDR', 'QUERY_STRING', 'REQUEST_URI', 'SCRIPT_NAME', 'PATH_INFO'
        );

        foreach ($needed as $name) {
            if (!isset($_SERVER[$name])) {
                $_SERVER[$name] = "";
            }
        }

        if (empty($_SERVER['HTTP_PROTO'])) {
            $_SERVER['HTTP_PROTO'] = 'http';
        }

        // forward
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
        }

        // forward
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
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

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $_SERVER['HTTP_PROTO'] = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        }

        // make sure host has a port if it's non-standard
        if (!in_array($_SERVER['SERVER_PORT'],array(80,443)) AND stripos($_SERVER['HTTP_HOST'], ':') === false) {
            $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'].":".$_SERVER['SERVER_PORT'];
        }

        // , means it's ben forwarded
        if (stripos($_SERVER['REMOTE_ADDR'], ',') !== false) {
            $_SERVER['REMOTE_ADDR'] = trim(array_shift(explode(',', $_SERVER['REMOTE_ADDR'])));
        }

        // no proto in host
        if (stripos($_SERVER['HTTP_HOST'], 'http') === false) {
            $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_PROTO'] . "://" . $_SERVER['HTTP_HOST'];
        }

        // get the file name
        $path = explode("/",$_SERVER['SCRIPT_FILENAME']);

        // need to get base tree
        $uri = explode('/',$_SERVER['SCRIPT_NAME']);
        $hostParts = explode(":", $_SERVER['HTTP_HOST']);

        if (isset($hostParts[1])) {
            $_SERVER['SERVER_PORT'] = $hostParts[1];
        }

        // general
        define("bProto",         $_SERVER['HTTP_PROTO']);
        define("bHost",          $_SERVER['HTTP_HOST']);
        define("bHostName",       array_shift($hostParts));
        define("bIp",            $_SERVER['REMOTE_ADDR']);
        define("bSelf",          bHost.$_SERVER['REQUEST_URI']);
        define("bPort",          $_SERVER['SERVER_PORT']);

        // Path Info
        if (isset($_SERVER['PATH_INFO']) AND !empty($_SERVER['PATH_INFO'])) {
            define("bPathInfo", $_SERVER['PATH_INFO']);
        }
        else {
            define("bPathInfo", str_replace("?".$_SERVER['QUERY_STRING'], "", $_SERVER['REQUEST_URI']));
        }

        // don't do this again
        define("bServerInit",   true);

    }


    public static function initFromEnvironment() {

            // initalize some request globals
            self::initGlobals();

            // name of
            if (!defined('bHostName')) {
                b::log("Unable to get host from server", array(), b::LogFatal); return;
            }

            // normalzie host
            $host = strtolower(bHostName);

            // start our assumeing we'll use the global project
            $project = b::config()->value('global.defaultProject');

            // figure out if we have a hostname that can
            // service this request
            foreach (b::config()->get('global') as $key => $value) {
                if ($value->exists('hostname')) {
                    foreach ($value['hostname']->value() as $hn) { // not hackernews -> hostname
                        if (strtolower($hn) == $host) {
                            $project = $key; break;
                        }
                        else if (strtolower(implode('.', array_slice(explode('.', $hn), -2))) == $host) {
                            $project = $key; break;
                        }
                    }
                }
            }

            // no project
            if ($project === false) {
                b::log("Unable to match hostname (%s) to project.", array($host), b::LogFatal); return;
            }

            // project
            $project = b::config('global')->value($project);

            // project
            if (isset($project['load'])) {
                b::load($project['load']);
            }

            // everything else is config
            if (isset($project['config'])) {
                b::config()->set('project', $project['config']);
            }

            // root
            if (b::config('project')->exists('root')) {
                $args['load'][] = b::config('project')->value('root');
            }

            //
            if (b::config()->exists('project.settings')) {
                b::settings()->set('project', b::config()->project->settings->value);
            }


        }

}

