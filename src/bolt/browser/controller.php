<?php

namespace bolt\browser;
use \b;

// plug into bolt
b::plug('controller', '\bolt\browser\controllerFactory');

////////////////////////////////////////////////////////////////////
/// @brief factory to generate a controller class
///
/// @param $class controller class
/// @return object controller
////////////////////////////////////////////////////////////////////
class controllerFactory extends \bolt\plugin {

    // type is singleton
    // since this is really a plugin dispatch
    public static $TYPE = "factory";

    // factory
    public static function factory($class='\bolt\browser\controller') {


        // lets do it
        return new $class();

    }

}

////////////////////////////////////////////////////////////////////
/// @brief controller interface
///
/// @interface
////////////////////////////////////////////////////////////////////
interface iController {
    public function init();                 // called when stating controller
    public function getTemplateDir();       // return template directory
    public function getParams();            // return params bucket
    public function getParam($name, $default=false);             // get single param from params bucket
    public function getParamValue($name, $default=false);        // get native value of param from bucket
    public function getGuid();              // get unique id of controller
    public function getLayout();            // return layout view
    public function run();                  // execute the controller
}

////////////////////////////////////////////////////////////////////
/// @brief base controlle class
/// @extends \bolt\event
///
/// @implements iController
/// @return voud
////////////////////////////////////////////////////////////////////
class controller extends \bolt\browser\view implements iController {


    private $_content = false;
    private $_fromInit = false;
    private $_data = array();
    private $_properties = array();

    // starter variables
    protected $templateDir = false;
    protected $layout = false;

    ////////////////////////////////////////////////////////////////////
    /// @brief construct a controller object. not object must be give
    ///         a unique id stored in $this->_guid
    ///
    ///
    /// @return object controller
    ////////////////////////////////////////////////////////////////////
    final public function __construct() {
        parent::__construct();  // construct our parent view

        // run init() and save return value
        // for check on run()
        $this->_fromInit = $this->init();

    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get the accept header from b::request
    /// @see \bolt\browser\request::getAccept
    ///
    /// @return accept header value
    ////////////////////////////////////////////////////////////////////
    public function getAccept() {
        return b::request()->getAccept();
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set the accept header from b::request
    /// @see \bolt\browser\request::getAccept
    ///
    /// @param $header
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setAccept($header) {
        b::response()->setAccept($header);
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set response content type
    /// @see \bolt\browser\response::setContentType
    ///
    /// @param $type
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setContentType($type) {
        b::response()->setContentType($type);
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get response content type
    /// @see \bolt\browser\response::setContentType
    ///
    /// @return content type
    ////////////////////////////////////////////////////////////////////
    public function getContentType() {
        return b::response()->getContentType();
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get the response status in b::response
    /// @see \bolt\browser\response::getStatus
    ///
    /// @return status
    ////////////////////////////////////////////////////////////////////
    public function getStatus() {
        return b::response()->getStatus();
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set the response status in b::response
    /// @see \bolt\browser\response::setStatus
    ///
    /// @param $status (int) http status
    /// @return \bolt\bucket params
    ////////////////////////////////////////////////////////////////////
    public function setStatus($status) {
        b::response()->setStatus($status);
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief execute the controller
    ///
    /// @return mixed response
    ////////////////////////////////////////////////////////////////////
    public function run() {

        // params from the request
        $params = b::request()->getParams();

        // before
        $this->fire('before');

        call_user_func(array($this, 'before'));

        // check
        if ($this->_fromInit AND b::isInterfaceOf($this->_fromInit, '\bolt\browser\iController')) {
            return $this->_fromInit;
        }

        // lets figure out what method was request
        $method = strtolower(b::request()->getMethod());

        $action = b::request()->getAction();

        // figure out how we handle this request
        // order goes
        // 1. dispatch
        // 2. method+action
        // 3. action
        // 4. method
        // 5. get

        if (method_exists($this, 'dispatch')) {
            $func = 'dispatch';
        }
        else if (method_exists($this, $method.$action)) {
            $func = $method.$action;
        }
        else if (method_exists($this, $action)) {
            $func = $action;
        }
        else if (method_exists($this, $method)) {
            $func = $method;
        }
        else if (method_exists($this, 'get')){
            $func = 'get';
        }
        else {
            return $this;
        }

        // reflect our method and add any
        // request params
        $m = new \ReflectionMethod($this, $func);

        // args we're going to send when we call
        $args = array();

        // method params
        if ($m->getNumberOfParameters() > 0) {
            foreach ($m->getParameters() as $i => $param) {
                $v = false;
                if ($params->exists($param->name)) {
                    $v = $params->getValue($param->name);
                }
                else {
                    $v = $params->getValue($i);
                }
                if ($v === false AND $param->isOptional()) {
                    $v = $param->getDefaultValue();
                }
                $args[] = $v;
            }
        }
        else {
            $args = $this->getParams()->asArray();
        }

        // go ahead an execute
        $resp = call_user_func_array(array($this, $func), $args);


        // if response is a view
        // render it
        if (is_string($resp)) {
            $this->setContent($resp);
        }

        call_user_func(array($this, 'after'));

        // after
        $this->fire('after');

        // me
        return $resp;

    }

}