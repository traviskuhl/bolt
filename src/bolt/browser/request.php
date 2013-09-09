<?php

namespace bolt\browser;
use \b;

// plug into b::request();
b::depend('bolt-browser-*');

/**
 * browser request class
 * @extends \bolt\plugin\singleton
 *
 */
class request {

	private $_accept = "text/html";
	private $_method = "GET";
	private $_get;       // $_GET
	private $_post;      // $_POST
	private $_request;   // $_REQUEST
	private $_headers;
	private $_input = "";
    private $_params = false; // route params
    private $_parsedInput = false;
    private $_route = false;


    /**
     * response object
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
            case 'patch':
            case 'put':
                return $this->getParsedInput();
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
            case 'parsedInput':
                return $this->getParsedInput();
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
    public function getInput($format=false) {
        return $this->_input;
    }

    public function getParsedInput() {
        if (!$this->_parsedInput) {
            $params = [];
            if (($this->_input{0} == '{' OR $this->_input{0} == '[') AND ($json = json_decode($this->_input, true)) != false) {
                $params = $json;
            }
            else {
                parse_str($this->_input, $params);
            }
            $this->_parsedInput = b::bucket($params);
        }
        return $this->_parsedInput;
    }


    public function getMethod() {
        return $this->_method;
    }

    public function getRoute() {
        return $this->_route;
    }

    public function setRoute($route) {
        $this->_route = $route;
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

        // hostparts
        $hostParts = parse_url($_SERVER['HTTP_HOST']);

        if (isset($hostParts['port'])) {
            $_SERVER['SERVER_PORT'] = $hostParts['port'];
        }

        // general
        define("bProto",         $_SERVER['HTTP_PROTO']);
        define("bHost",          $_SERVER['HTTP_HOST']);
        define("bHostName",      $hostParts['host']);
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

