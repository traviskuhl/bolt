<?php

namespace bolt;
use \b;
use \Crypt_Blowfish;

// plug
b::plug('cookie', '\bolt\cookie');

class cookie extends plugin\singleton {

	public function create($parts=array()) {
		return new oCookie($parts);
	}

    ////////////////////////////////////////////////
    /// @brief add url params to a url
    ///
    /// @param $url base url
    /// @param $params array of params to add
    ///
    /// @return string url with additional params
    ////////////////////////////////////////////////
	public function set($name, $value, $expires=false, $domain=false, $secure=false, $http=false, $path="/") {

		if (is_array($expires)) {
			foreach ($expires as $k => $v) {
				${$k} = $v;
			}
		}

		$o = new oCookie(array(
			'name' => $name,
			'value' => $value,
			'expires' => $expires,
			'domain' => $domain,
			'secure' => $secure,
			'http' => $http,
			'path' => $path
		));

		return $o->set();

	}

	public function delete($name) {
		$o = new oCookie();
		return $o->name($name)->delete();
	}

	public function get($name) {
		$o = new oCookie();
		return $o->name($name)->get()->value();
	}

}

class oCookie {

	// local
	private $_prefix = false;
	private $_encrypt = false;

	// cookie parts
	private $_name;
	private $_value;
	private $_expires = false;
	private $_domain = false;
	private $_secure = false;
	private $_http = false;
	private $_path = "/";

	public function __construct($parts=array()) {

		// from config
		$this->_domain = b::settings("project")->value("cookies.domain", false);
		$this->_prefix = b::settings("project")->value("cookies.prefix", false);

		// any parts
		foreach ($parts as $k => $v) {
			$this->__set($k, $v);
		}

	}

	public function __get($name) {
		if (property_exists($this, "_$name")) {
			return $this->{"_$name"};
		}
		return false;
	}

	public function __set($name, $value) {
		return $this->__call($name, array($value));
	}

	public function __call($name, $args) {
		if (property_exists($this, "_$name") AND count($args) > 0) {
			$this->{"_$name"} = $args[0];
		}
		else if (property_exists($this, "_$name")) {
			return $this->{"_$name"};
		}
		return $this;
	}

	public function set() {
		$expires = (is_numeric($this->_expires) ? $this->_expires : strtotime($this->_expires));
		$value = $this->_value;
		$name = $this->_get_name();

		if ($this->_encrypt) {
			$value = $this->_crypt($value);
		}
		else if (!is_string($value)) {
			$value = $this->_freeze($value);
		}

		// set
		return setcookie($name, $value, $expires, $this->_path, $this->_domain, $this->_secure, $this->_http);

	}

	public function get() {
		$name = $this->_get_name();

		if (b::param($name, false, $_COOKIE)) {
			$value = $_COOKIE[$name];
			if (substr($value, 0, 2) == 'e:') {
				$value = $this->_crypt($value);
			}
			else if ($value{0} == ':') {
				$value = $this->_thaw($value);
			}

			$this->_value = $value;
		}

		return $this;
	}

	public function delete() {
		$name = $this->_get_name();
		$value = false;
		$expires = time()+1;

		// set
		return setcookie($name, $value, $expires, $this->_path, $this->_domain, $this->_secure, $this->_http);
	}

	private function _get_name() {
		return ($this->_prefix ? implode("_", array($this->_prefix, $this->_name)) : $this->_name);
	}

	private function _crypt($value) {
		$salt = b::settings()->value("project.salt", "");
		$bf = new Crypt_Blowfish('cbc');
       	$bf->setKey($salt);
		if (is_string($value) AND substr($value, 0, 2) == 'e:') {
			return json_decode(trim($bf->decrypt(base64_decode(substr($value,2)))), true);
		}
		else {
			return 'e:'.base64_encode($bf->encrypt(json_encode($value)));
		}
	}

	private function _freeze($value) {
		$str = base64_encode(json_encode($value));
		return ':'.b::md5($str).$str;
	}

	private function _thaw($value) {
		$sig = substr($value, 1, 32);
		$e = substr($value, 33);
		return ( b::md5($e) == $sig ? json_decode(base64_decode($e), true) : false );
	}

}
