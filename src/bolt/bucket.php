<?php

namespace bolt {
use \b;

// bucket
b::plug('bucket', '\bolt\bucket');

class bucket extends \bolt\plugin\factory implements \Iterator, \ArrayAccess {

    private $_guid;
	private $_parent;
	private $_root;
	private $_data = array();
    private $_returnObject = true;

    public $value = false;


    ////////////////////////////////////////////////////////////////////
    ///
    ////////////////////////////////////////////////////////////////////
    public static function factory($args=array()) {
        if (count($args) == 0) { return new \bolt\bucket(); }
        //
        if (is_array($args)) {
            return new bucket($args);
        }
        else {
            return new bucket\bString(false, $args, false);
        }

    }

	////////////////////////////////////////////////////////////////////
    /// @brief constrcut a new bucket
    ///
    /// @param $data array of data
    /// @param $root root element
    /// @param $parent parent element
    /// @return void
    ////////////////////////////////////////////////////////////////////
	public function __construct($data=array(), $root=false, $parent=false) {
        $this->_guid = uniqid();
		$this->_data = (is_array($data) ? $data : array());
		$this->_parent = $parent;
		$this->_root =  $root;
	}

    public function returnObject($return) {
        $this->_returnObject = $return;
        return $this;
    }

    public function getGuid() {
        return $this->_guid;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set the data
    ///
    /// @param $data data array
    /// @return setl
    ////////////////////////////////////////////////////////////////////
    public function setData($data=array()) {
        $this->_data = $data;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief return a clean data array
    ///
    /// @return array of data
    ////////////////////////////////////////////////////////////////////
    public function getData() {
        $data = $this->_data;
        array_walk_recursive($data, function(&$item){
            if (is_object($item) AND is_a($item, '\bolt\bucket\bString')) {
                $item = (string)$item;
            }
            else if (is_object($item) AND method_exists($item, 'asArray')) {
                $item = $item->asArray();
            }
        });
        return $this->filter_recursive($data, function($value){
            return !($value === null);
        });
    }

        public function filter_recursive($o, $cb) {
            foreach ($o as &$value) {
                if (is_array($value)) {
                    $value = $this->filter_recursive($value, $cb);
                }
            }
            return array_filter($o, $cb);
        }

    ////////////////////////////////////////////////////////////////////
    /// @brief return a clean data array
    ///
    /// @return array of data
    ////////////////////////////////////////////////////////////////////
    public function asArray() {
        return $this->getData();
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief return a clean data as json
    ///
    /// @return json string
    ////////////////////////////////////////////////////////////////////
    public function asJson() {
        return json_encode($this->getData());
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief return a clean serialized array
    ///
    /// @return json string
    ////////////////////////////////////////////////////////////////////
    public function asSerialized() {
        return serialize($this->getData());
    }


    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC get a value
    ///
    /// @param $name name of variable
    /// @see get()
    /// @return value
    ////////////////////////////////////////////////////////////////////
	public function __get($name) {
		return $this->get($name);
	}

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC set a value
    ///
    /// @param $name name of variable
    /// @param $value value or variable
    /// @see set()
    /// @return self
    ////////////////////////////////////////////////////////////////////
	public function __set($name, $value){
		$this->set($name, $value);
		return $this;
	}

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC test if a value is set
    ///
    /// @param $name name of variable
    /// @see exists()
    /// @return bool if value exists
    ////////////////////////////////////////////////////////////////////
    public function __isset($name) {
        return $this->exists($name);
    }

    public function __call($name, $args) {
        array_unshift($args, $name);
        return call_user_func_array(array($this, 'getValue'), $args);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC return data as json string
    ///
    /// @see toJson()
    /// @return json string
    ////////////////////////////////////////////////////////////////////
    public function __toString() {
        return $this->asJson();
    }

    public function isEmpty() {
        return (count($this->_data) == 0 ? true : false);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get a variable or return deafult value
    ///
    /// @param $name name of variable
    /// @param $default value to return if name not set
    /// @param $useDotNamespace
    /// @return value
    ////////////////////////////////////////////////////////////////////
	public function get($name, $default=null, $useDotNamespace=true) {
        $oName = $name; // placeholder for future use
        if ($default === null) {$default = new bucket(false, $name, $this);}   // always return an object
        if (is_string($default) OR is_array($default)) { $default = (is_array($default) ? new bucket($default, $name, $this) : new bucket\bString($name, $default, $this)); }
        if (!is_string($name) AND !is_integer($name)) {return $default;}              // always a key name

        // does default have any .
        if (stripos($name, '.') !== false AND $useDotNamespace === true) {
            $parts = explode('.', $name);
            $name = array_pop($parts);
            $var = $this;
            foreach ($parts as $part) {
                $var = $var->get($part);
            }
            if (is_a($var, '\bolt\bucket')) {
                return $var->get($name, $default);
            }
            else {
                // fallback to check without namespace
                return $this->get($oName, $default, false);
            }
        }

        if (!array_key_exists($name, $this->_data)) { $this->_data[$name] = $default; }

        // return
        $return = $default;


		// figureo ut if it's an object
		if (is_object($this->_data[$name])) {
			$return = $this->_data[$name];
		}
		else if (is_array($this->_data[$name])) {
			$return =  new bucket($this->_data[$name], $name, $this);
		}
        else {
		  $return = new bucket\bString($name, $this->_data[$name], $this);
        }

        // return object
        if ($this->_returnObject === false) {
            if (is_a($return, '\bolt\bucket')) {
                return $return->asArray();
            }
            else if (is_a($return, '\bolt\bucket\bString')) {
                return $return->value;
            }
            else if (is_object($return) AND !is_a($return, 'Closure')) {
                $return->returnObject(false);
                return $return;
            }
            else {
                return $return;
            }
        }

        return $return;

	}

    ////////////////////////////////////////////////////////////////////
    /// @brief get an un modified variable
    ///
    /// @param $name name of variable
    /// @param $default value to return if name not set
    /// @return value
    ////////////////////////////////////////////////////////////////////
	public function getValue($name, $default=false) {
        $r = $this->get($name, $default);
		return (is_a($r, '\bolt\bucket\bString') ? $r->getValue($default) : $r);
	}

    ////////////////////////////////////////////////////////////////////
    /// @brief set a variable value
    ///
    /// @param $name name of variable
    /// @param $value value for variable
    /// @return self
    ////////////////////////////////////////////////////////////////////
	public function set($name, $value=false) {
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				$this->set($k, $v);
			}
		}
		else if (is_string($name) OR is_integer($name)) {
            $this->setValue($name, $value);
			if ($this->_parent) {
				$this->_parent->setValue($this->_root, $this->_data);
			}
		}
		return $this;
	}

    ////////////////////////////////////////////////////////////////////
    /// @brief set a variable with modification
    ///
    /// @param $name name of variable
    /// @param $value value of name
    /// @return self
    ////////////////////////////////////////////////////////////////////
	public function setValue($name, $value) {
        $this->fire('set', array('new' => $value, 'prev' => $this->getValue($name)));
        $this->fire("set{$name}", array('new' => $value, 'prev' => $this->getValue($name)));
		$this->_data[$name] = $value;
		return $this;
	}


    ////////////////////////////////////////////////////////////////////
    /// @brief push a value onto an array
    ///
    /// @param $value value to push
    /// @return self
    ////////////////////////////////////////////////////////////////////
	public function push($value, $key=null) {
		if ($key !== null) {
            $this->_data[$key] = $value;
        }
        else {
            $this->_data[] = $value;
        }
		if ($this->_parent) {
			$this->_parent->setValue($this->_root, $this->_data);
		}
        return $this;
	}
        public function add($value) { return $this->push($value); }


    ////////////////////////////////////////////////////////////////////
    /// @brief map values in an array
    ///
    /// @param $cb callback function
    /// @return self
    ////////////////////////////////////////////////////////////////////
	public function map($cb) {
		foreach ($this->_data as $key => $value) {
			call_user_func($cb, $key, $value, $this);
		}
		return $this;
	}

    ////////////////////////////////////////////////////////////////////
    /// @brief return
    ///
    /// @param $cb callback function
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function reduce($cb) {
        $resp = array();

        // cb is a string
        if(is_string($cb)) {
            if ($cb{0} == '$') {
                $key = substr($cb,1);
                $cb = function($item) use ($key) {
                    $i = $item->$key;
                        if (is_object($i)) {
                            $i = $i->asArray();
                        }
                    return $i;
                };
            }
        }

        foreach ($this as $item) {
            if (($r = $cb($item)) !== false) {
                if (is_array($r)) {
                    $resp = array_merge($r, $resp);
                }
                else {
                    $resp[] = $r;
                }
            }
        }

        return b::bucket($resp);

    }

    public function filter($cb) {
        $s = b::bucket();
        foreach ($this as $item) {
            if ($cb($item) !== false) {
                $s->push($item);
            }
        }
        return $s;
    }

    public function each($cb) {
        $resp = array();

        foreach ($this as $key => $item) {
            $resp[$key] = $cb($item);
        }

        // give bacl
        return $resp;

    }

    public function slice($start, $len=null) {
        $s = clone $this; $s->clear();
        $parts = array_slice($this->_map, $start, $len, true);
        foreach ($parts as $k => $i) {
            $s->push($this->offsetGet($i), $k);
        }
        return $s;
    }

    public function shuffle() {
        $s = clone $this; $s->clear();
        $c = array_flip($this->_map);
        shuffle($this->_map);
        foreach ($this->_map as $i) {
            $k = $c[$i];
            $s->push($this->offsetGet($i), $k);
        }
        return $s;
    }


    ////////////////////////////////////////////////////////////////////
    /// @brief is the needle in the array
    ///
    /// @param $needle value in the data array
    /// @return bool of result
    ////////////////////////////////////////////////////////////////////
	public function in($needle) {
		foreach ($this->_data as $value) {
			if ($value === $needle) {
				return true;
			}
		}
		return false;
	}

    public function implode($str) {
        return implode($str, $this->asArray());
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief does a value exist
    ///
    /// @param $name name of value
    /// @return bool if it exists
    ////////////////////////////////////////////////////////////////////
	public function exists($name) {
		return (array_key_exists($name, $this->_data));
	}

    ////////////////////////////////////////////////////////////////////
    /// @brief get an item by index
    ///
    /// @param $idx index of item
    /// @return item value
    ////////////////////////////////////////////////////////////////////
	public function item($idx) {
        if ($idx === 'first') {
            $idx = key(array_slice($this->_data, 0, 1));
        }
        else if ($idx === 'last') {
            $idx = key(array_slice($this->_data, -1));
        }
		return $this->get($idx);
	}

    ////////////////////////////////////////////////////////////////////
    /// @brief set a value at index
    ///
    /// @param $offset offset value to set
    /// @param $value value
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->_data[] = $value;
        } else {
            $this->_data[$offset] = $value;
        }
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief check if an offset exists
    ///
    /// @param $offset offset name
    /// @return bool if offset exists
    ////////////////////////////////////////////////////////////////////
    public function offsetExists($offset) {
        return isset($this->_data[$offset]);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief unset an offset
    ///
    /// @param $offset offset name
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function offsetUnset($offset) {
        unset($this->_data[$offset]);
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get an offset value
    ///
    /// @param $offset offset name
    /// @return value
    ////////////////////////////////////////////////////////////////////
    public function offsetGet($offset) {
        return isset($this->_data[$offset]) ? $this->get($offset) : null;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief rewind array pointer
    ///
    /// @return self
    ////////////////////////////////////////////////////////////////////
    function rewind() {
        reset($this->_data);
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief current array pointer
    ///
    /// @return self
    ////////////////////////////////////////////////////////////////////
    function current() {
        $var = current($this->_data);
        return (is_array($var) ? b::bucket($var) : $var);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief array key pointer
    ///
    /// @return key
    ////////////////////////////////////////////////////////////////////
    function key() {
          $var = key($this->_data);
        return $var;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief advance array pointer
    ///
    /// @return current value
    ////////////////////////////////////////////////////////////////////
    function next() {
        $var = next($this->_data);
        return $var;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief is the current array pointer valid
    ///
    /// @return current value
    ////////////////////////////////////////////////////////////////////
    function valid() {
        $var = $this->current() !== false;
        return $var;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get count of data
    ///
    /// @return count
    ////////////////////////////////////////////////////////////////////
    function count() {
        return count($this->_data);
    }

}

}

namespace bolt\bucket {

class bString {

    private $_value = false;
    private $_parent = false;
    private $_key = false;

    // modifiers
    private $_modifiers = array('encode','decode','toupper','tolower','ucfirst');

    ////////////////////////////////////////////////////////////////////
    /// @brief construct a bucket string
    ///
    /// @param $key name of key
    /// @param $value starter value
    /// @param $parent bucket pointer
    /// @return void
    ////////////////////////////////////////////////////////////////////
    public function __construct($key, $value, $parent) {
        $this->_value = $value;
        $this->_key = $key;
        $this->_parent = $parent;
    }

    public function isEmpty() {
        return !$this->_value;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief map values in an array
    ///
    /// @param $name name of modifier
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function __get($name) {
        if ($name == 'value') {
            return $this->_value;
        }
        else if (in_array($name, $this->_modifiers)) {
            return call_user_func(array($this, $name));
        }
        return $this->get();
    }

    public function __set($name, $value) {
        $this->set($value);
    }

    public function __call($name, $args) {
        return $this->_value;
    }

    public function __toString() {
        return (string)$this->_value;
    }

    public function get($default=false) {
        return ($this->_value ?: $default);
    }
    public function getValue($default=false) {
        return $this->get($default);
    }

    public function set($value) {
        if (is_array($value)) {
            $value = b::bucket($value);
        }
        $this->_value = $value;
        if ($this->_parent) {
            $this->_parent->set($this->_key, $value);
        }
        return $this;
    }

    public function getModifiers(){
        return $this->_modifiers;
    }

    // string functions
    public function encode($q=ENT_QUOTES) {
        return htmlentities($this->_value, $q, 'utf-8', false);
    }
    public function decode($q=ENT_QUOTES) {
        return html_entity_decode($this->_value, $q, 'utf-8');
    }
    public function toUpper() {
        return strtoupper($this->_value);
    }
    public function toLower() {
        return strtolower($this->_value);
    }
    public function ucfirst() {
        return ucfirst($this->_value);
    }
    public function cast($type) {
        settype($this->_value, $type);
        return $this;
    }
    public function totime() {
        return strtotime($this->_value);
    }

    public function exists() {
        return true;
    }

}

class proxy {
    private function _bucketProxy($name, $args=array()) {
        $var = $this->{$this->bucketProxy};
        return call_user_func_array(array($var, $name), $args);
    }

    public function __set($name, $value) {
        return call_user_func(array($this, '_bucketProxy'), 'set', func_get_args());
    }
    public function __get($name) {
        return call_user_func(array($this, '_bucketProxy'), 'get', func_get_args());
    }
    public function __isset($name) {
        return call_user_func(array($this, '_bucketProxy'), 'exists', func_get_args());
    }

}

}