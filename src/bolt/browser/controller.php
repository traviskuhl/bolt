<?php

namespace bolt\browser;
use \b;


/**
 * controller interface
 *
 * @interface
 */
interface iController {
    public function build(); // build
    public function run();  // execute the controller
}

/**
 * base controlle class
 * @extends \bolt\event
 *
 * @implements iController
 * @return voud
 */
class controller extends \bolt\browser\view implements iController {

    // render
    private $_content = false;
    private $_data = array();
    private $_properties = array();
    private $_hasRendered = false;

    // starter variables
    protected $templateDir = false;
    protected $layout = false;

    /**
     * get the accept header from b::request
     * @see \bolt\browser\request::getAccept
     *
     * @return accept header value
     */
    public function getAccept() {
        return b::request()->getAccept();
    }

    /**
     * set the accept header from b::request
     * @see \bolt\browser\request::getAccept
     *
     * @param $header
     * @return self
     */
    public function setAccept($header) {
        b::response()->setAccept($header);
        return $this;
    }

    /**
     * set response content type
     * @see \bolt\browser\response::setContentType
     *
     * @param $type
     * @return self
     */
    public function setContentType($type) {
        b::response()->setContentType($type);
        return $this;
    }

    /**
     * get response content type
     * @see \bolt\browser\response::setContentType
     *
     * @return content type
     */
    public function getContentType() {
        return b::response()->getContentType();
    }

    /**
     * get the response status in b::response
     * @see \bolt\browser\response::getStatus
     *
     * @return status
     */
    public function getStatus() {
        return b::response()->getStatus();
    }

    /**
     * set the response status in b::response
     * @see \bolt\browser\response::setStatus
     *
     * @param $status (int) http status
     * @return \bolt\bucket params
     */
    public function setStatus($status) {
        b::response()->setStatus($status);
        return $this;
    }

    /**
     * execute the controller
     *
     * @return mixed response
     */
    final public function build() {

        // params from the request
        $params = b::request()->getParams();

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

        // no templates
        if (!$this->hasTemplate()) {
            $root = b::config()->getValue("project.templates");
            $parts = explode(DIRECTORY_SEPARATOR, str_replace('\\', DIRECTORY_SEPARATOR, $m->getDeclaringClass()->name));
            while(count($parts) > 0) {
                $file = $root."/".implode("/", $parts).".template.php";
                if (file_exists($file)) {
                    $this->setTemplate($file); break;
                }
                array_shift($parts);
            }
        }

        // no template
        if (!$this->hasLayout()) {
            $root = b::config()->getValue("project.templates")."/layouts";
            $parts = explode(DIRECTORY_SEPARATOR, str_replace('\\', DIRECTORY_SEPARATOR, $m->getDeclaringClass()->getParentClass()->name));

            while(count($parts) > 0) {
                $file = $root."/".implode("/", $parts).".template.php";
                if (file_exists($file)) {
                    $this->setLayout($file); break;
                }
                array_shift($parts);
            }
            $file = b::config()->getValue("project.templates")."/layout.template.php";
            if (file_exists($file)) {
                $this->setLayout($file);
            }
        }

        // if response is a view
        // render it
        if (is_string($resp)) {
            $this->setContent($resp);
        }

    }

    /**
     * execute the controller
     *
     * @return mixed response
     */
    public function run() {
        return $this->render();
    }

}