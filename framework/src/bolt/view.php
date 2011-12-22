<?php

namespace bolt;
use \b as b;

class view {

    // params
    private $_params = array();
    private $_method = false;
    
    // some things we're going to need
    private $_content = false;
    private $_data = false;
    private $_headers = array();
    private $_status = 200;
    
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
        
            // set
            case 'setContent':
                return ($this->_content = $args[0]);
            case 'setData':
                return ($this->_data = $args[0]);
            case 'setHeaders':
                return ($this->_headers = $args[0]);
            case 'setStatus':
                return ($this->_status = $args[0]);
            
            // add 
            case 'addHeader':
                return ($this->_headers[$args[0]] = $args[1]);
            case 'addData':
                return ($this->_data[$args[0]] = $args[1]);
            case 'addParams':
                return ($this->_params[$args[0]] = $args[1]);
        
        };                
    }
    
    
    
    // protected
    protected function template($tmpl) {
        return $tmpl;
    }

    // function
    public function __construct($params=array(), $method=false) {
        $this->_params = $params;
        $this->_method = $method;
    }

    // param
    public function param($name, $value=false) {
        if ($value) {
            return $this->_params[$name] = $value;
        }
        else {
            return $this->_param[$name];
        }
    }

    // get
    public function __get($name) {
        return $this->param($name);
    }
    
    // set
    public function __set($name, $value) {
        return $this->param($name, $value);
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
        $this->setContent(b::render(array(
            'template' => $this->template($tmpl),
            'vars' => $vars
        )));
    
        // me
        return $this;
        
    }

}