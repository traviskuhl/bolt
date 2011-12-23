<?php

// namespace me
namespace bolt\source;
use \b as b;

// plug
b::plug('webservice', '\bolt\source\webservice');

class webservice extends \bolt\plugin\factory {

    // config
    private $_config = array(
        'host'      => false,
        'port'      => 80,
        'protocol'  => 'http',
        'headers'   => array(),
        'method'    => 'curl',
        'auth'      => array(),        
    );
    
    // oauth
    private $_oauth = false;
    private $_curl = false;
    
    // construct
    public function __construct($cfg=array()) {    
        $this->_config = array_merge($this->_config, $cfg);
    }
    
    // set and get
    public function __get($name) {
        return (array_key_exists($name, $this->_config) ? $this->_config[$name] : false);
    }
    
    // set
    public function __set($name, $value=false) {
        return ($this->_config[$name] = $value);
    }
    
    // request
    public function request() {
    
        // args
        $args = func_get_args();
    
        // url
        $args[0] = "{$this->protocol}://{$this->host}".($this->port!=80?":{$this->port}":"")."/".ltrim($args[0],'/');    
            
        // route to proper func
        switch($this->method) {
        
            // straight curl request
            case 'curl':
                return call_user_func_array(array($this, 'curlRequest'), $args);
                
            // oauth request
            case 'oauth':
                return call_user_func_array(array($this, 'oauthRequest'), $args);
                
        };
    
    }
    
    public function getCurl() {
        return $this->_curl;
    }
    public function getOauth() {
        return $this->_oauth;
    }
    
    protected function curlRequest($url, $params=array(), $method="GET", $headers=array()) {    
    
		// headers
		$headers = array_merge($this->headers, $headers);        
    
        // add our params 
        if ( $method == 'GET' ) {
            $url = b::addUrlParams($url, $params);
        }
                
        // new curl request
        $this->_curl = curl_init();

        // set some stuff
        curl_setopt($this->_curl, CURLOPT_URL, $url);
        curl_setopt($this->_curl, CURLOPT_HEADER, 0);    
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($this->_curl, CURLOPT_CONNECTTIMEOUT,5);        
        curl_setopt($this->_curl, CURLOPT_CUSTOMREQUEST, $method);        
        curl_setopt($this->_curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, 0);    				
				
				
        // add headers
        curl_setopt($this->_curl,CURLOPT_HTTPHEADER, array_map(function($k, $v){
        	return "$k:$v";
        },array_keys($headers), $headers));
    
        // add params
        if ( $method == 'POST' OR $method == 'PUT' ) {
	        curl_setopt($this->_curl,CURLOPT_POSTFIELDS, $params );
        }
        
        // auth
        if ( isset($this->auth['username']) ) {
        	curl_setopt($this->_curl, CURLOPT_USERPWD, "{$this->auth['username']}:{$this->auth['password']}");
        }
        
        // make the request
        $result = curl_exec($this->_curl);   
            
        // return our re    
        $r = new webserviceResponse(
            $this,
            $result, 
            curl_getinfo($this->getCurl(),CURLINFO_HTTP_CODE), 
            curl_getinfo($this->getCurl())
        );

        // close our curl
        curl_close($this->_curl);                
        
        // give it 
        return $r;
    
    }
    
    // oauth
    protected function oauthRequest($url, $params=array(), $method="GET", $headers=array()) {
        
        // if oauth isn't here
        if (!class_exists('OAuth')) { return false; }
        
        // if we don't have oauth
        if (!$this->_oauth) {
            $this->_oauth = new OAuth($this->auth['key'], $this->auth['secret']);
        }
        
        // fetch it 
        $this->_oauth->fetch($url, $params, $method, $headers);
    
        // info
        $info = $this->_oauth->getLastResponseInfo();
        
        // return 
        return new webserviceResponse(
            $this,
            $this->_oauth->getLastResponse(),
            $info['http_code'],
            $info            
        );
    
    }
    
}

class webserviceResponse {
    
    private $_code = false;
    private $_body = false;
    private $_error = false;
    private $_info = array();
    private $_req = array();
    
    public function __construct($req, $body, $code, $info) {
    
        // set our body
        $this->_body = $body;
        
        // code
        $this->_code = $code;
        
        // info
        $this->_info = $info;
        
        // req
        $this->_req = $req;    
    
    }
    
    // magic
    public function __call($name, $args) {
        
        switch($name) {
            
            // code
            case 'code':
            case 'getCode':
                return $this->_code;            
            
            // text
            case 'getText':
            case 'text':
                return $this->_body;
            
            // json
            case 'json':
            case 'getJson':
                return json_decode($this->_body, true);

            // json
            case 'jsonObject':
            case 'getJsonObject':
                return json_decode($this->_body);
                
            // xml
            case 'xml':
            case 'getXml':
                return simplexml_load_string($this->_body);
                
            // info
            case 'info';
            case 'getInfo':
                return $this->_info;
                
            // req
            case 'request':
            case 'getRequest':  
                return $this->_req;
                    
        };
    
        return false;
    
    }
    

}
