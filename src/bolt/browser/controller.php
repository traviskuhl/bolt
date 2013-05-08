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
class controller extends \bolt\event implements iController {

    private $_guid = false;
    private $_layout = false;
    private $_params = false;
    private $_content = false;
    private $_fromInit = false;
    private $_data = array();

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
        $this->_guid = uniqid();    // make this object unique
        $this->_params = b::bucket();   // hold our params

        // run init() and save return value
        // for check on run()
        $this->_fromInit = $this->init();

        // check if a layout property is set
        if ($this->layout) {
            $this->setLayout($this->layout);
        }

    }

    ////////////////////////////////////////////////////////////////////
    /// @brief init function run after construct
    ///
    /// @return voud
    ////////////////////////////////////////////////////////////////////
    public function init() {}

    ////////////////////////////////////////////////////////////////////
    /// @brief called by render before build
    ///
    /// @return void
    ////////////////////////////////////////////////////////////////////
    protected function before() {}

    ////////////////////////////////////////////////////////////////////
    /// @brief called by render after build and render
    ///
    /// @return void
    ////////////////////////////////////////////////////////////////////
    protected function after() {}

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC set a param. passthrough to bucket::set()
    /// @see \bolt\bucket::set()
    ///
    /// @param $name
    /// @param $value
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function __set($name, $value) {
        $this->_params->set($name, $value);
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC return a property. fallback to params.
    ///     request -> b::request()
    ///     response -> b::response()
    ///     * -> \bolt\bucket::get()
    ///
    /// @param $name
    /// @return mixed value
    ////////////////////////////////////////////////////////////////////
    public function __get($name) {
        switch($name) {
            case 'request':
                return b::request();
            case 'response':
                return b::response();
            default:
              return $this->_params->get($name);
        };
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get the content of the controller
    ///
    /// @return controller content
    ////////////////////////////////////////////////////////////////////
    public function getContent() {
        return $this->_content;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set the content of the controller
    ///
    /// @param $content content of controller
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setContent($content) {
        if (b::isInterfaceOf($content, '\bolt\browser\iView')) {
            $content = $this->render($content);
        }
        $this->_content = $content;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief return unique id for object
    ///
    /// @return string guid
    ////////////////////////////////////////////////////////////////////
    public function getGuid() {
        return $this->_guid;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get the value of the template dir
    ///
    /// @return string template directory
    ////////////////////////////////////////////////////////////////////
    public function getTemplateDir() {
        return $this->templateDir;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief return params bucket
    ///
    /// @return \bolt\bucket params
    ////////////////////////////////////////////////////////////////////
    public function getParams() {
        return $this->_params;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief return a single param from bucket. passthrough to \bolt\bucket::get
    ///
    /// @param $name
    /// @param $default
    /// @return mixed value
    ////////////////////////////////////////////////////////////////////
    public function getParam($name, $default=false) {
        return $this->_params->get($name);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief return native value from params bucket
    ///
    /// @param $name
    /// @param $value
    /// @return mixed value
    ////////////////////////////////////////////////////////////////////
    public function getParamValue($name, $default=false) {
        return $this->_params->getValue($name);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set the layout view
    ///
    /// @param $layout (string|\bolt\browser\view) path to template or view object
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setLayout($layout) {
        if (is_string($layout)) {
            $file = $this->getTemplateDir()."/".$layout;
            $layout = b::view()
                        ->setFile($file)
                        ->setController($this);
        }
        $this->_layout = $layout;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief return layout view
    ///
    /// @return \bolt\browser\view
    ////////////////////////////////////////////////////////////////////
    public function getLayout() {
        return $this->_layout;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief check if the controller has a layout view
    ///
    /// @return bool
    ////////////////////////////////////////////////////////////////////
    public function hasLayout() {
        return !($this->_layout === false);
    }

    public function setData($data) {
        $this->_data = $data;
        return $this;
    }

    public function getData() {
        return $this->_data;
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


        // see if there's a layout
        if ($this->hasLayout()) {
            $this->setContent(
                    $this->getLayout()
                        ->setParams(array('child' => $this->getContent()))
                        ->render()
            );
        }

        call_user_func(array($this, 'after'));

        // after
        $this->fire('after');

        // me
        return $resp;

    }


    ////////////////////////////////////////////////////////////////////
    /// @brief render a template file and set controller content
    /// @see \bolt\render::render
    ///
    /// @param $file path to template file
    /// @param $vars array of variable
    /// @param $render name of render plugin
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function renderTemplate($file, $vars=array(), $render=false) {
        $this->setContent(b::render(array(
                'render' => $render,
                'file' => $this->getTemplateDir()."/".$file,
                'controller' => $this,
                'vars' => $vars
            )));
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief render a string and set as controller content
    ///
    ///
    /// @param $str string to render
    /// @param $vars array of variable
    /// @param $render name of render plugin
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function renderString($str, $vars=array(), $render=false) {
        $this->setContent(b::render(array(
                'render' => $render,
                'string' => $str,
                'controller' => $this,
                'vars' => $vars
            )));
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief render a given view in the context of this controller
    ///
    ///
    /// @param $view (string|\bolt\browser\view) view class name or view object
    /// @return rendered view
    ////////////////////////////////////////////////////////////////////
    public function render($view) {

        // string
        if (is_string($view) AND class_exists($view, true)) {
            $view = b::view($view);
        }

        // make sure view implements bolt\browser\view
        if (!b::isInterfaceOf($view, '\bolt\browser\iView')) {
            return false;
        }

        // set our view
        return $view
                ->setController($this)
                ->render();

    }

}