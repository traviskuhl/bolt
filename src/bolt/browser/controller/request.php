<?php

namespace bolt\browser\controller;
use \b;

class request extends \bolt\browser\controller {


    public function location() {
        return call_user_func_array(array(b::bolt(),'location'), func_get_args());
    }


    /**
     * execute the controller
     *
     * @return mixed response
     */
    public function invoke($action='build', $params=array()) {
        $route = $this->_route;
        $method = strtolower($this->getRequest()->getMethod());

        // route
        if ($route) {

            // are there models that need to be setup
            if ($route->getModel() AND property_exists($this, 'models')) {
                foreach ($route->getModel() as  $name) {
                    if (!array_key_exists($name, $this->models)) {continue;}
                    $info = $this->models[$name];

                    $model = b::model($info['model']);

                    if (!isset($info['method'])) {
                        $info['method'] = 'findById';
                    }
                    if (!isset($info['args'])) {
                        $info['args'] = array('$'.$model->getPrimaryKey());
                    }
                    if (isset($info['args'])) {
                        array_walk_recursive($info['args'], function(&$item, $key) use ($params){
                            if (is_string($item) AND $item{0} == '$') {
                                if (substr($item,0,9) == '$request.') {
                                    $item = $this->get(substr($item,9));
                                }
                                else {
                                    $_ = substr($item,1);
                                    $item = (array_key_exists($_, $params) ? $params[$_] : false);
                                }
                            }
                        });
                    }
                    $params[$name] = call_user_func_array(array($model, $info['method']), $info['args']);

                    // unless model is optional
                    if ($method !== 'post' AND b::param('optional', false, $info) === false AND $params[$name]->loaded() === false) {
                        return b::browser()->error(404, "Unable to load model '$name' ({$info['model']})");
                    }

                }
            }

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
        else if ($action) {
            if (method_exists($this, $method.$action)) {
                $act = $method.$action;
            }
            else if (method_exists($this, $action)) {
                $act = $action;
            }
            else {
                $act = 'noRouteActionError';
            }
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

        // execute render
        return parent::invoke($act, $params);

    }

    protected function noRouteActionError() {
        $method = $this->_method;
        $action = $this->_route->getAction();
        return b::browser()->error("Unable to find route action '$method $action'", 404);
    }

    // postModel
    protected function postModel(\bolt\browser\request $req) {
        $route = $this->getRoute();
        $models = $route->getModel();
        $name = array_shift($models);

        // no model exsti
        if (!property_exists($this, $name)) {
            return b::browser()->error(500, 'unable to load model');
        }

        // what's the model we need
        $model = $this->{$name};

        // get all of our post request
        $post = $req->post->get($name)->asArray();

        // set in our model
        $model->set($post)->save();

        // url
        $url = b::url($route->getName(), $model->asArray());

        // relocate there
        b::location($url);

    }

    // putModel
    protected function putModel(\bolt\browser\request $req) {
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

    // deleteModel
    protected function deleteModel(\bolt\browser\request $req) {
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