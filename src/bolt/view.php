<?php

namespace bolt;
use \b as b;

// view
b::plug('view', '\bolt\viewFactory');

// source
class viewFactory extends plugin {

    // type is singleton 
    // since this is really a plugin dispatch
    public static $TYPE = "factory";
    
    // factory
    public static function factory() {
        
        // args
        $args = func_get_args();
        
        // give them a view 
        if (!class_exists($args[0], true)) { return false; }
    
        // params
        $params = (isset($args[1]) ? $args[1] : array());
        $method = (isset($args[2]) ? $args[2] : false);
    
        // lets do it 
        return new $args[0]($params, $method);
    
    }    

}


// the view
class view {
    
    // some things we're going to need
    private $_content = false;    
    private $_data = array();
    private $_wrap = -1;
    private $_hasExecuted = false;
    private $_guid = false;
    private $_args = array();

    // share
    protected $request;
    protected $response;
    protected $params;
    protected $accept = false;

    // function
    public function __construct($args=array()) {

        // guid
        $this->_guid = uniqid();
    
        // request
        $this->request = b::request();
        $this->response = b::response();

        // local params
        $this->params = b::bucket();
        $this->_args = b::bucket($args);

        // if accept is false,
        // get it from teh request
        if ($this->accept === false) {
            $this->accept = $this->request->getAccept();
        }

        // init function in sub view
        if (method_exists($this, 'init')) {
            $this->init();
        }

    }    
    
    // magic set
    public function __call($name, $args) {
        switch($name)  {
            
            // get
            case 'getContent':
                return $this->_content;
            case 'getData':
                return $this->_data;
            case 'getHeaders':
                return $this->request->getHeaders();
            case 'getStatus':
                return $this->response->getStatus();
            case 'getParams':
                return $this->request->getParams();
            case 'getInput':
                return $this->request->getInput();
            case 'getAccept':
                return $this->accept;
            case 'getMethod':
                return $this->request->getMethod();
            case 'getGuid':
                return $this->_guid;
            case 'getArgs':
                return $this->_args;
            case 'getArg':
                return $this->_args->get($name);
        
            // set
            case 'setContent':
                return ($this->_content = $args[0]);
            case 'setData':
                return ($this->_data = $args[0]);
            case 'setHeaders':
                return ($this->response->headers->add($args[0]));
            case 'setStatus':
                return ($this->response->setStatus($args[0]));
            case 'setWrap':
                return ($this->_wrap = $args[0]);            
            case 'setAccept':
                return ($this->accept = $args[0]);            
            
            // add 
            case 'setHeader':            
            case 'addHeader':
                return ($this->response->headers->set($args[0], $args[1]));
            case 'addData':
                return ($this->_data[$args[0]] = $args[1]);            
            case 'addAccept':
                return ($this->response->setAccept($args[0]));
        
        };                
        return $this;
    }
    
    public function getWrap() {
        return $this->_wrap;
    }

    // protected
    protected function template($tmpl) {
        return $tmpl;
    }

    // param
    public function getParam($name, $default=false) {  
        if ($this->params->exists($name)) {
            return $this->params->get($name, $default);
        }
        else if ($this->_args->exists($name)) {
            return $this->_args->get($name, $default);
        }
        else {
            return $this->request->params->get($name, $default);
        }        
    }
    
    public function setParam($name, $value=false) {
        $this->params->set($name, $value);
        return $this;
    }    

    public function addParam($name, $value) {
        $this->params->get($name)->push($value);
        return $this;
    }     
    public function setParams(\bolt\bucket $params) {
        $this->params = $params;
        return $this;
    }

    public function getParams() {
        return $this->params;
    }

    // get
    public function __get($name) {
        return $this->getParam($name);
    }
    
    // set
    public function __set($name, $value) {
        return $this->setParam($name, $value);
    }

    // render
    public function render($tmpl=false, $args=array()) {
    
        // no template means return just the render
        if (!$tmpl) {
            return b::render($args);
        }                
    
        // return our rendered
        $this->setContent(b::render()->template(
            $this->template($tmpl),
            array(
                'view' => $this
            )
        ));
    
        // me
        return $this;
        
    }
    
    public function hasExecuted($done=false) {
        return ($done ? ($this->_hasExecuted = true) : $this->_hasExecuted);
    }
    
    // execute the view
    public function execute($as=false) {

        // i'm the view,
        // but i could change if i'm forwarded
        $view = $this;
        
        // method
        $method = $this->request->getMethod();
        $accept = $this->request->getAccept();
        
        // guid
        $guid = $this->_guid();
    
        // preresp
        preresp:        

        // what function to run
        $func = false;
                    
        // module
        if ($as == 'module' AND method_exists($view, 'module')) {
            $func = 'module';
            // $resp = call_user_func_array(array($view, 'module'), $params);        
        }

        // if our accept header says it's ajax
        else if ($accept == 'text/javascript;text/ajax' AND method_exists($view, 'ajax')) {
            $func = 'ajax';

            // $resp = call_user_func_array(array($view, 'ajax'), $params);
        }                    

        // there's a dispatch
        else if (method_exists($view, '_dispatch')) { 
            $func = '_dispatch';
            //$resp = call_user_func_array(array($view, '_dispatch'), $params);        
        }        

        // does this method exist for this objet        
        else if (method_exists($view, $method)) {
            $func = strtolower($method);
            // $resp = call_user_func_array(array($view, $method), $params);                
        }                    
        
        // a get to fall back on 
        else if (method_exists($view, 'get')) {
            $func = 'get';
            // $resp = call_user_func_array(array($view, 'get'), $params);                
        }

        
        // reflect our method
        $m = new \ReflectionMethod($view, $func); 

        // args we're going to send when we call
        $args = array();

        // params
        if ($m->getNumberOfParameters() > 0) {
            // loop through and find our names
            foreach ($m->getParameters() as $i => $param) {
                if ($this->_args->exists($param->name)) {
                    $args[] = $this->_args->getValue($param->name);
                }
                else {                        
                    $args[] = $this->_args->getValue($i);
                }
            }
        }
        else {
            $args = $this->getArgs()->asArray();
        }

        // go ahead an execute
        $resp = call_user_func_array(array($view, $func), $args);
        
        // we've executed, just in case they
        // try returning the same view
        $view->hasExecuted(true);

        // see if they want to forward to a different view
        if ($resp AND is_string($resp) AND class_exists($resp)) {
            
            // replace the view and retry the resp
            $view = new $resp($view->getParams(), $view->getMethod());
            
            // resp is false again
            $resp = false;
            
            // go back
            goto preresp;
            
        }
        
        // is a view, but make sure it hasn't been executed
        // already. TODO: add better check for same view
        else if (is_object($resp) AND $resp->hasExecuted() !== true) {
        
            // if it's this view just stop
            if ($guid != $resp->getGuid()) {
            
                // set our response as a view
                $view = $resp;
                
                // set guid
                $guid = $resp->getGuid();

                // go back
                goto preresp;
                
            }
            
        }

        // if we have a wrapper
        // let's do that now
               // wrap
        if (isset($args['wrap']) AND $args['wrap'] AND $view->getWrap() === -1) {
            $view->setWrap($this->template($args['wrap'], $args, $view));
        }
        else if (stripos($view->getWrap(), '.template.php') !== false) {             
            $view->setWrap(b::render()->template(
                $view->getWrap(),
                array(
                    'view' => $view
                )
            ));
        }

        // give back this
        return $view;    
    
    }

}