<?php

namespace bolt;
use \b;

// bucket
b::plug('bucket', '\bolt\bucket');

class bucket extends \bolt\plugin\factory {

	private $_parent;
	private $_root;
	private $_data = array();

	// bucket
	public function __construct($data=array(), $root=false, $parent=false) {
		$this->_data = $data;
		$this->_parent = $parent;
		$this->_root=  $root;
	}

	public function __get($name) {
		return $this->get($name);
	}

	public function __set($name, $value){
		$this->set($name, $value);
		return $this;
	}

	public function get($name, $default=false) {
		if (!array_key_exists($name, $this->_data)) { return $default; }

		// figureo ut if it's an object
		if (is_a($this->_data[$name], '\bolt\bucket')) {
			return $this->_data[$name];
		}
		else if (is_array($this->_data[$name])) {
			return new bucket($this->_data[$name], $name, $this);
		}
		return new bucket\item($name, $this->_data[$name], $this);
	}

	public function getValue($name, $default=false) {	
		return ($this->exists($name) ? $this->_data[$name] : $default);
	}

	public function set($name, $value=false) {
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				$this->set($k, $v);
			}
		}
		else {
			$this->_data[$name] = $value;
			if ($this->_parent) {
				$this->_parent->setValue($this->_root, $this->_data);
			}
		}
		return $this;
	}

	public function setValue($name, $value) {
		$this->_data[$name] = $value;
		return $this;
	}

	public function push($value) {
		$this->_data[] = $value;
		if ($this->_parent) {
			$this->_parent->setValue($this->_root, $this->_data);
		}
	}

	public function getData() {
		return $this->_data;
	}

	public function map($cb) {
		foreach ($this->_data as $key => $value) {
			call_user_func($cb, $key, $value, $this->_data);
		}
		return $this;
	}

	public function in($needle) {
		foreach ($this->_data as $value) {
			if ($value === $needle) {
				return true;
			}
		}
		return false;
	}

	public function exists($name) {
		return (array_key_exists($name, $this->_data));
	}

	public function __toString() {
		return json_encode($this->_data);
	}
	public function asArray() {
		return $this->getData();
	}

	public function item($idx) {
		return $this->getValue($idx);
	}

}

namespace bolt\bucket;

class item {
	private $_key;
	private $_parent;
	public $value;
	public function __construct($key,$value,$parent) {
		$this->_parent = $parent;
		$this->_key = $key;
		$this->value = $value;
	}
	public function value($default) {
		return ($this->value ?: $default);
	}
	public function set($value) {
		$this->value = $value;
		$this->parent->set($this->key, $value);
		return $this;
	}	
	public function __get($name) {
		return $this->value->__get($name);
	}
	public function __set($name, $value) {
		return $this->value->__set($name, $value);
	}
	public function __call($name, $args) {
		if (is_object($this->value) AND method_exists($this->value, $name)) {
			return call_user_func_array(array($this->value, $name), $args);
		}
		return false;
	}
	public function __toString() {
		return (string)$this->value;
	}
}