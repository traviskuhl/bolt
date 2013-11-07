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

    protected $cache = array(
        'ttl' => null
    );

    // construct
    public function __construct($cfg=array()) {
        $this->_config = array_merge($this->_config, $cfg);
    }


    public function model(/* $model, $type, ... */) {
        $args = func_get_args();
        $model = array_shift($args);
        $type = array_shift($args);


        $source = false;

        if ($model->source() AND array_key_exists('curl', $model->source())) {
            $source = $model->source()['curl'];
        }
        else {

            // models
            $models = b::settings()->value("project.source.models", array());

            // class name
            $name = get_class($model);

            if (array_key_exists($name, $models) AND array_key_exists('curl', $models[$name])) {
                $source = $models[$name]['curl'];
            }

        }

        // no source
        if (!$source) {
            // FIX THIS SHIT ASS error
            die("NO SOURCE NAME");
        }


        // ttl
        if (isset($source['ttl'])) {
            $this->cache['ttl'] = $source['ttl'];
        }

        // uri
        $uri = $source['uri'][$type];

        if (is_callable($uri)) {
            list($type, $uri, $args) = $uri($args);
        }
        else {
            if ($type == 'findById') {
                $uri = b::tokenize($uri, array("key" => $args[0]));
                $args[0] = array();
            }
            if ($type == 'find' OR $type == 'findById') {
                $type = 'query';
                $args[0] = array('query' => $args[0]);
            }
        }

        // add back our path
        array_unshift($args, $uri);

        // call it
        $resp = call_user_func_array(array($this, $type), $args);


        if (is_array($source) AND array_key_exists('response', $source)) {
            if (is_callable($source['response'])) {
                $resp = $source['response']($resp, $model);
            }
            else {
                $body = $resp->data();
                $resp = b::a($body[$source['response']]);
            }
        }

        // return a result
        return $resp;

    }

    public function query($path, $query, $args=array()) {
        return $this->request($path, array_merge($query, $args), 'GET');
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

    private function _getCacheKey($args) {
        $prefix = b::settings()->value("project.source.cache.prefix", false);
        return md5($prefix.get_called_class().serialize($args));
    }

    private function _cache($args, $store=false, $ttl = false) {

        // if the settings say no
        if (b::settings()->value('project.source.cache.active') !== true) {return false;}

        // key
        $key = $this->_getCacheKey($args);

        // store
        if ($store){

            $_ttl = b::param('ttl', b::settings()->value('project.source.cache.ttl', 0), $this->cache);

            return b::cache()->set($key, $store, ($ttl ?: $_ttl));

        }
        else {

            // get
            $resp = b::cache()->get($key);

            // good
            if ($resp) {
                return $resp;
            }

            return false;

        }

    }


    // request
    public function request($path, $params=array(), $method="GET", $headers=array(), $useIniFilePost=false) {

        // cid
        $cid = $this->_getCacheKey(func_get_args());

        // resp
        if ( ($resp = $this->_cache($cid)) !== false) {
            // return our re
            return $this->_last = new curlResponse(
                $this,
                $resp[0],
                $resp[1]['http_code'],
                $resp[1]
            );
        }

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

        if (b::env() != 'prod') {
            error_log("API: $url");
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

        if (isset($this->_config['sendCookies']) AND $this->_config['sendCookies'] === true) {
            $headers['cookies'] = $_COOKIE;
        }

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

        $info = curl_getinfo($this->getCurl());

        if (b::env() == 'dev') {
            $msg = "[API] $url".($method=='GET'?"?".urldecode(http_build_query($params)):false)." - {$info['total_time']} / ns:{$info['namelookup_time']} / co:{$info['connect_time']}";
            error_log($msg);
        }


        // close our curl
        curl_close($this->_curl);

        // resp
        $this->_cache($cid, array(
                $result,
                $info
            ), b::param('ttl', false, $params));

        // return our re
        $r = $this->_last = new curlResponse(
            $this,
            $result,
            $info['http_code'],
            $info
        );

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
                $t = $this->_info['content_type'];
                if (stripos($t, 'application/json') !== false OR stripos($t, 'text/javascript') !== false)  {
                    return $this->json();
                }
                else if (stripos($t, 'application/xml') !== false OR stripos($t, 'text/xml') !== false) {
                    return $this->xml();
                }
                else {
                    return $this->_body;
                }

        };

        return false;

    }


}
