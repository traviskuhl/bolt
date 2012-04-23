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

    // params
    private $_params = array();
    private $_method = false;
    
    // some things we're going to need
    private $_content = false;
    private $_data = false;
    private $_headers = array();
    private $_status = 200;
    private $_input = false;
    private $_wrap = -1;
    private $_hasExecuted = false;
    private $_guid = false;
    
    // this should be overrideable by the child
    protected $accept = array('*/*');
    
    // function
    public function __construct($params=array(), $method=false) {
    
        // guid
        $this->_guid = uniqid();
    
        // set some stuff
        $this->_params = (is_array($params) ? $params : array());
        $this->_method = strtolower($method);
        
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
                return $this->_headers;
            case 'getStatus':
                return $this->_status;
            case 'getParams':
                return $this->_params;
            case 'getInput':
                return ($this->_input ? $this->_input : file_get_contents("php://input"));
            case 'getAccept':
                return $this->accept;
            case 'getMethod':
                return $this->_method;
            case 'getGuid':
                return $this->_guid;
        
            // set
            case 'setContent':
                return ($this->_content = $args[0]);
            case 'setData':
                return ($this->_data = $args[0]);
            case 'setHeaders':
                return ($this->_headers = $args[0]);
            case 'setStatus':
                return ($this->_status = $args[0]);
            case 'setWrap':
                return ($this->_wrap = $args[0]);
            case 'setInput':
                return ($this->_input = $args[0]);                
            case 'setAccept':
                return ($this->accept = $args[0]);
            case 'setParams':
                return ($this->_params = $args[0]);
            
            // add 
            case 'setHeader':            
            case 'addHeader':
                return ($this->_headers[$args[0]] = $args[1]);
            case 'addData':
                return ($this->_data[$args[0]] = $args[1]);
            case 'addParam':
                return ($this->_params[$args[0]] = $args[1]);
            case 'addAccept':
                return ($this->accept[] = $args[0]);
        
        };                
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
        if (array_key_exists($name, $this->_params) AND $this->_params[$name]) {
            return $this->_params[$name];
        }
        
        // fallback
        return p($name, $default);
        
    }
    
    public function setParam($name, $value=false) {
        return $this->_params[$name] = $value;
    }    

    public function addParam($name, $value, $key=false) {
        if (!array_key_exists($name, $this->_params)) { $this->_params[$name] = array(); }
        ($key ? ($this->_params[$name][$key] = $value) : $this->_params[$name][] = $value);        
        return $this;
    }     

    public function getParams() {
        return $this->_params;
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
    
        // merge in our params
        $vars = array_merge($args, $this->_params);    
    
        // no template means return just the render
        if (!$tmpl) {
            return b::render($args);
        }                
    
        // return our rendered
        $this->setContent(b::render()->template(
            $this->template($tmpl),
            $vars,
            $this
        ));
    
        // me
        return $this;
        
    }
    
    public function hasExecuted($done=false) {
        return ($done ? ($this->_hasExecuted = true) : $this->_hasExecuted);
    }
    
    // execute the view
    public function execute($params=array(), $accept=false) {
                
        // i'm the view,
        // but i could change if i'm forwarded
        $view = $this;
        
        // method
        $method = $this->_method;
        
        // guid
        $guid = $this->_guid();
    
        // preresp
        preresp:
        
        // no params
        if (!is_array($params)) { $params = array(); }

        // our resp
        $resp = false;
            
        // if our accept header says it's ajax
        if ($accept == 'text/javascript;text/ajax' AND method_exists($view, 'ajax')) {
            $resp = call_user_func_array(array($view, 'ajax'), $params);
        }        
        
        // module
        else if ($method == 'module' AND method_exists($view, 'module')) {
            $resp = call_user_func_array(array($view, 'module'), $params);        
        }

        // there's a dispatch
        else if (method_exists($view, '_dispatch')) { 
            $resp = call_user_func_array(array($view, '_dispatch'), $params);        
        }        

        // does this method exist for this objet        
        else if (method_exists($view, $method)) {
            $resp = call_user_func_array(array($view, $method), $params);                
        }                    
        
        // a get to fall back on 
        else if (method_exists($view, 'get')) {
            $resp = call_user_func_array(array($view, 'get'), $params);                
        }
        
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
    
        // give back this
        return $view;    
    
    }

}