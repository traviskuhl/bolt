<?php

// namespace me
namespace bolt\source;
use \b;

class curl extends base {

    const NAME = "curl";

    // config
    private $_config = array(

        // host information
        'host'      => false,
        'port'      => false,
        'scheme'    => 'http',
        'user'      => false,
        'pass'      => false,
        'basePath'  => false,

        // some default stuff
        'headers'   => array(),
        'method'    => 'curl',
        'curlOpts'  => array(),
        'timeout'   => 15,
        'models'    => array()

    );

    // oauth
    private $_oauth = false;
    private $_curl = false;
    private $_last = false;

    // construct
    public function __construct($cfg=array()) {
        $this->_config = array_merge($this->_config, $cfg);
    }

    private function _mapModelEp($type, $model, $data=array()) {
        $name = get_class($model);
        $map = (array_key_exists($name, $this->_config['models']) ? $this->_config['models'][$name] : array());

        // model has endpoints
        if (is_array($model->endpoints)) {
            $map = $model->endpoints;
        }

        // this type has an endpoint
        if (array_key_exists($name, $map) AND array_key_exists($type, $map[$name])) {
            return b::tokenize($map[$name][$type], $data);
        }
        else if (array_key_exists('uri', $map)) {
            switch($type) {
                case 'query':
                case 'insert':
                    return $map['uri'];
                case 'update':
                    return b::tokenize($map['item'], array("key" => $data));
                default:
                    return b::tokenize($map['item'], array("key" => $data[$model->getPrimaryKey()]));
            };
        }

        return false;
    }

    private function _getResponse($model, $resp) {
        $data = $resp->data();
        $name = get_class($model);
        $map = (array_key_exists($name, $this->_config['models']) ? $this->_config['models'][$name] : array());

        // model has endpoints
        if (is_array($model->endpoints)) {
            $map = $model->endpoints;
        }

        if (is_array($map) AND is_array($data) AND isset($map['root']) AND array_key_exists($map['root'], $data)) {
            return \bolt\bucket::byType($data[$map['root']]);
        }
        return \bolt\bucket::byType(array());
    }

    public function model(/* $model, $type, ... */) {
        $args = func_get_args();
        $model = array_shift($args);
        $type = array_shift($args);
        $mapRow = false;

        if ($type == 'row' AND $args[0] !== $model->getPrimaryKey()) {
            $type = 'query';
            $args[0] = array($args[0] => $args[1]);
            $args[1] = array();
            $mapRow = true;
        }

        if ($type == 'row') {
            $path = $this->_mapModelEp($type, $model, array($args[0] => $args[1]));
        }
        else {
            $path = $this->_mapModelEp($type, $model, $args[0]);
        }

        // get our table
        array_unshift($args, $path);

        // json encode query
        if ($type == 'query' AND isset($map['jsonEncodeQuery']) AND $map['jsonEncodeQuery'] == true) {
            $args[1] = json_encode($args[1]);
        }


        // call it
        $resp = $this->_getResponse($model, call_user_func_array(array($this, $type), $args));

        if ($mapRow) {
            $resp = $resp->item(0);
        }

        // return a result
        return  $resp;

    }

    public function query($path, $query, $args=array()) {
        return $this->request($path, array_merge(array('query' => $query), $args), 'GET');
    }

    public function row($path, $field, $value, $args=array()) {
        return $this->request($path, $args, 'GET');
    }

    public function insert($path, $data, $args=array()) {
        $resp = $this->request($path, $data, 'POST');
        return $resp;
    }

    public function update($path, $id, $data, $args=array()) {
        return $this->request($path, $data, 'PUT');
    }

    public function count($ep, $query, $args=array()) {

    }

    public function delete($table, $id, $args=array()) {

    }

    public function getLastResponse() {
        return $this->_last;
    }

    public function config($key, $val=false) {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->_config[$k] = $v;
            }
        }
        else if ($key AND $val) {
            $this->_config[$key] = $val;
        }
        else {
            return $this->__set($key);
        }
    }

    // set and get
    public function __get($name) {
        return (array_key_exists($name, $this->_config) ? $this->_config[$name] : false);
    }

    // set
    public function __set($name, $value=false) {
        return ($this->_config[$name] = $value);
    }

    public function getCurl() {
        return $this->_curl;
    }

    // request
    public function request($path, $params=array(), $method="GET", $headers=array(), $useIniFilePost=false) {

        if (is_string($path)) {
            $path = array(
                    'scheme' => $this->scheme,
                    'host' => $this->host,
                    'port' => $this->port,
                    'path' => b::path($this->basePath, $path)
                );
        }

        $url = b::buildUrl($path);

		// headers
        if (is_array($headers)) {
		  $headers = array_merge($this->headers, $headers);
        }

        // add our params
        if ($method == 'GET' AND count($params) > 0) {
            $url = b::addUrlParams($url, $params);
        }

        // new curl request
        $this->_curl = curl_init();

        // set some stuff
        curl_setopt($this->_curl, CURLOPT_URL, $url);
        curl_setopt($this->_curl, CURLOPT_HEADER, 0);
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_curl, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($this->_curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->_curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->_curl, CURLINFO_HEADER_OUT, 1);
        curl_setopt($this->_curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->_curl, CURLOPT_INFILESIZE, -1);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, NULL);

        // cookies
        if (array_key_exists('cookies', $headers)) {

            // cookies
            $cookies = array();

            // headers
            foreach ($headers['cookies'] as $k => $v) {
                $cookies[] = "$k=$v";
            }

            // cookies
            curl_setopt($this->_curl, CURLOPT_COOKIE, implode(';',$cookies));
            unset($headers['cookies']);

        }

        // add params
        if ( $method == 'POST' ) {

            if ($useIniFilePost) {

                                // length
                $len = strlen($params);


                // write our params to tmp
                $fp = fopen('php://temp/maxmemory:256000', 'w');
                fwrite($fp, $params);
                fseek($fp, 0);

                // put it
                curl_setopt($this->_curl, CURLOPT_BINARYTRANSFER, true);
                curl_setopt($this->_curl, CURLOPT_INFILE, $fp);
                curl_setopt($this->_curl, CURLOPT_INFILESIZE, $len);
                curl_setopt($this->_curl, CURLOPT_POST, TRUE);

            }
            else {
                // post
    	        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, (is_array($params) ? http_build_query($params) : (string)$params));
                curl_setopt($this->_curl, CURLOPT_POST, TRUE);

                // var_dump($params); die;

            }

        }
        if ($method == 'PUT' OR $method == 'DELETE') {

            // if params is an array
            if (is_array($params)) {
                $params = http_build_query($params);
            }

                // write our params to tmp
                $fp = fopen('php://temp/maxmemory:256000', 'w');
                fwrite($fp, $params);
                fseek($fp, 0);

                // length
                $len = strlen($params);

                // put it
                curl_setopt($this->_curl, CURLOPT_BINARYTRANSFER, true);
                curl_setopt($this->_curl, CURLOPT_PUT, TRUE);
                curl_setopt($this->_curl, CURLOPT_INFILE, $fp);
                curl_setopt($this->_curl, CURLOPT_INFILESIZE, $len);

        }


        // auth
        if ( $this->_config['user'] AND $this->_config['pass'] ) {
        	curl_setopt($this->_curl, CURLOPT_USERPWD, "{$this->_config['user']}:{$this->_config['pass']}");
        }

        // add headers
        curl_setopt($this->_curl,CURLOPT_HTTPHEADER, array_map(function($k, $v){
            if ($k AND $v) {
            	return "$k:$v";
            }
        },array_keys($headers), $headers));

        // curl ops
        if ($this->curlOpts) {
            curl_setopt_array($this->_curl, $this->curlOpts);
        }

        // make the request
        $result = curl_exec($this->_curl);

        // return our re
        $r = $this->_last = new curlResponse(
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

}

class curlResponse {

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

            // erro
            case 'error':
            case 'getError':
                return $this->_error;

            // status code
            case 'status':
            case 'getStatus':
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

            // parse
            case 'parsed':
            case 'getParsed':
                $p = array();
                parse_str($this->_body, $p);
                return $p;

            // req
            case 'request':
            case 'getRequest':
                return $this->_req;

            case 'data':
                switch($this->_info['content_type']) {
                    case 'application/json':
                    case 'text/javascript':
                        return $this->json();
                    case 'application/xml':
                    case 'text/xml':
                        return $this->xml();
                };
                return $this->_body;

        };

        return false;

    }


}
