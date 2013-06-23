<?php

namespace bolt\browser\controller;
use \b;

class request extends \bolt\browser\controller {

    // render
    private $_content = false;
    private $_data = array();
    private $_properties = array();
    private $_hasRendered = false;
    private $_route = false;

    /**
     * set the route
     *
     * @param $route
     * @return self
     */
    public function setRoute($route) {
        $this->_route = $route;
        return $this;
    }

    /**
     * get the route for controller
     *
     * @return route
     */
    public function getRoute() {
        return $this->_route;
    }


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
     * @param $route route class
     * @return mixed response
     */
    public function run($method, $route) {
        $method = strtolower($method);

        // get some stuff from the route
        $params = $route->getParams();
        $action = $route->getAction();

        // are there models that need to be setup
        if (property_exists($this, 'models')) {
            foreach ($this->models as $name => $info) {
                $model = b::model($info['model']);
                if (!isset($info['method'])) {
                    $info['method'] = 'findById';
                }
                if (!isset($info['args'])) {
                    $info['args'] = array('$'.$model->getPrimaryKey());
                }
                foreach ($info['args'] as $i => $arg) {
                    if ($arg{0} == '$') {
                        $_ = substr($arg,1);
                        $info['args'][$i] = (array_key_exists($_, $params) ? $params[$_] : false);
                    }
                }
                $params[$name] = call_user_func_array(array($model, $info['method']), $info['args']);
            }
        }


        // check
        if ($this->_fromInit AND b::isInterfaceOf($this->_fromInit, '\bolt\browser\iController')) {
            return $this->_fromInit;
        }

        // figure out how we handle this request
        // order goes
        // 1. dispatch
        // 2. method+action
        // 3. action
        // 4. method
        // 5. get
        // 6. build

        if (method_exists($this, 'dispatch')) {
            $act = 'dispatch';
        }
        else if (method_exists($this, $method.$action)) {
            $act = $method.$action;
        }
        else if (method_exists($this, $action)) {
            $act = $action;
        }
        else if (method_exists($this, $method)) {
            $act = $method;
        }
        else if (method_exists($this, 'get')){
            $act = 'get';
        }
        else {
            $act = 'build';
        }

        // route
        $this->setRoute($route);

        // set our action
        $this->setAction($act);

        // execute render
        $this->render($params);

        // return me
        return $this;
    }

    // modelPost
    protected function postModel(\bolt\browser\request $req) {
        $route = $this->getRoute();
        $name = $route->getModel();

        // no model exsti
        if (!property_exists($this, $name)) {
            return b::browser()->error(500, 'unable to load model');
        }

        // what's the model we need
        $model = $this->{$name};

        // get all of our post request
        $post = $req->post->asArray();


        // set in our model
        $model->set($post)->save();

        // url
        $url = b::url($route->getName(), $model->asArray());

        // relocate there
        b::location($url);

    }



}