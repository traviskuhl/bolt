<?php

namespace bolt\bucket;
use \b;

/**
 * bucket array wrapper
 */
class bArray implements \bolt\iBucket, \ArrayAccess, \Iterator, \Countable {

    private $_bguid = false;
    private $_root = false;
    private $_parent = false;
    private $_data = array();

    /**
     * construct a new bArray object
     *
     * @param array $data array of data
     * @param string $root key name
     * @param object $parent parent objects
     *
     * @return \bolt\bucket\bArray new instances
     */
    public function __construct($data, $root=false, $parent=false) {
        $this->_bguid = uniqid('b');
        $this->_root = $root;
        $this->_parent = $parent;

        // set our data
        $this->set(is_array($data) ? $data : array());
    }

    /**
     * unique id for this instance
     *
     * @return string guid
     */
    public function bGuid() {
        return $this->_bguid;
    }

    /**
     * test if a value is set
     *
     * @param $name name of variable
     * @see \bolt\bucket\bArray::exists()
     * @return bool if value exists
     */
    public function __isset($name) {
        return $this->exists($name);
    }

    /**
     * unset a value
     *
     * @param string $name name of key to unset
     * @see \bolt\bucket\bArray::remove
     *
     * @return bool
     */
    public function __unset($name) {
        return $this->remove($name);
    }

    /**
     * get a value
     *
     * @param string $name name of value to get
     * @see \bolt\bucket\bArray::get
     *
     * @return mixed value
     */
    public function __get($name){
        if ($name == 'value') {return $this->value(); }
        return $this->get($name);
    }

    /**
     * set a value
     *
     * @param string $name name of key to set
     * @param mixed $value value
     * @see \bolt\bucket\bArray::set
     *
     * @return self
     */
    public function __set($name, $value) {
        $this->set($name, $value);
        return $this;
    }

    /**
     * return data as json string
     *
     * @see \bolt\bucket\bArray::toJson()
     * @return json string
     */
    public function __toString() {
        return ($this->_data ? $this->asJson() : "");
    }

    /**
     * return native array value
     *
     * @param string $name key name of requested value
     * @param mixed $default default value to return if key doesn't exist
     *
     * @return mixed[] value of key
     */
    public function value($name=null, $default=array()) {
        if ($name !== null) {
            return $this->get($name, $default)->value();
        }
        return $this->normalize();
    }

    /**
     * return a normalized/native array
     *
     * @return array normalized array
     */
    public function normalize() {
        $normal = array();
        foreach ($this->_data as $k => $v) {
            $normal[$k] = $v->normalize();
        }
        return $normal;
    }

    public function export() {
        return var_export($this->normalize(), true);
    }

    /**
     * return a value for a give key name
     *
     * @param mixed $name name of key
     * @param mixed $default default value to return if no key exists
     * @param bool $useDotNamespace expand dot notation in name
     *
     * @param \bolt\iBucket data value for name or $default as bucket
     */
    public function get($name, $default=null, $useDotNamespace=true) {
        $oName = $name; // placeholder for future use

        if (is_object($name)) {
            $name = (string)$name;
        }

        // does default have any .
        if ($name!==null && stripos($name,'(') !== false ) {
            $x = stripos($name,'(');
            $func = substr($name, 0, $x);
            if (method_exists($this, $func)) {
                $args = explode(',', trim(substr($name, $x), '()'));
                return call_user_func_array(array($this, $func), $args);
            }
            return $default;
        }
        else if (stripos($name, '.') !== false AND $useDotNamespace === true) {
            $parts = explode('.', $name);
            $name = array_pop($parts);
            $var = $this;
            foreach ($parts as $part) {
                $var = $var->get($part);
            }
            if (b::isInterfaceOf($var, '\bolt\iBucket') ) {
                return $var->get($name, $default);
            }
            else {
                return $this->get($oName, $default, false);
            }
        }

        // is data set
        if (array_key_exists($name, $this->_data)) {
            $default = $this->_data[$name];
        }

        if ($default === null) {
            $default = array();
        }

        return \bolt\bucket::byType($default, $name, $this);

    }

    /**
     * set a value for named key
     *
     * @param mixed $name name of key or array of values to set
     * @param mixed[] value of named key
     *
     * @return self
     */
    public function set($name, $value=false) {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->set($key, $value);
            }
            return $this;
        }

        // set the data
        $this->_data[$name] = \bolt\bucket::byType($value, $name);

        // give me back
        return $this;

    }

    /**
     * unset a value from teh array
     *
     * @param string $name name or array of names to unset
     *
     * @return self
     */
    public function remove($name) {
        if (is_array($name)) {
            foreach ($name as $key) {
                $this->remove($key);
            }
            return $this;
        }
        if (array_key_exists($name, $this->_data)) {
            unset($this->_data[$name]);
        }
        return $this;
    }

    /**
     * return a clean data array
     *
     * @return array of data
     */
    public function asArray() {
        $native = array();
        foreach ($this->_data as $key => $obj) {
            $native[$key] = $obj->normalize();
        }
        return $native;
    }

    /**
     * return a clean data as json
     *
     * @return json string
     */
    public function asJson() {
        return json_encode($this->asArray());
    }

    /**
     * return a clean serialized array
     *
     * @return json string
     */
    public function asSerialized() {
        return serialize($this->asArray());
    }

    /**
     * @package   Config_Lite
     * @author    Patrick C. Engel <pce@php.net>
     * @copyright 2010-2011 <pce@php.net>
     * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
     * get the bucket as an ini string
     *
     * @param $key key to get first
     * @return ini string
     */
    public function asIni($key=false) {
        $data = ($key ? $this->get($key)->asArray() : $this->asArray());
        $content = '';
        $sections = '';
        $globals  = '';
        if (!empty($data)) {
            // 2 loops to write `globals' on top, alternative: buffer
            foreach ($data as $section => $item) {
                if (!is_array($item)) {
                    $value    = $item;
                    $globals .= $section . ' = "' . $value .'"'."\n";
                }
            }
            $content .= $globals;
            foreach ($data as $section => $item) {
                if (is_array($item)) {
                    $sections .= "\n[" . $section . "]\n";
                    foreach ($item as $key => $value) {
                        if (is_array($value)) {
                            foreach ($value as $arrkey => $arrvalue) {
                                $arrvalue  = $arrvalue;
                                $arrkey    = $key . '[' . (is_int($arrkey) ? "" : $arrkey) . ']';
                                $sections .= $arrkey . ' = "' . $arrvalue.'"'
                                            ."\n";
                            }
                        } else {
                            $value     = $value;
                            $sections .= $key . ' = "' . $value .'"'."\n";
                        }
                    }
                }
            }
            $content .= $sections;
        }
        return $content;
    }


    /**
     * @brief push a value onto an array
     *
     * @param $value value to push
     * @return self
     */
    public function push($value, $key=null) {

        if ($key !== null) {
            $this->_data[$key] = \bolt\bucket::byType($value, $key, $this);
        }
        else {
            $this->_data[] = \bolt\bucket::byType($value, false, $this);
        }
        return $this;
    }

    public function pop() {
        $var = array_pop($this->_data);
        return \bolt\bucket::byType($var);
    }

    public function shift() {
        $var = array_shift($this->_data);
        return \bolt\bucket::byType($var);
    }

    /**
     * @brief map values in an array
     *
     * @param $cb callback function
     * @return self
     */
    public function map($cb) {
        foreach ($this->_data as $key => $value) {
            $this->_data[$key] = call_user_func($cb, $key, $value, $this);
        }
        return $this;
    }

    /**
     * @brief return
     *
     * @param $cb callback function
     * @return self
     */
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

    public function filter($by) {
        $s = new bArray(array());
        foreach ($this->normalize() as $key => $value) {
            if (is_string($by) AND $key == $by) {
                continue;
            }
            else if (is_array($by) AND in_array($key, $by)) {
                continue;
            }
            else if (is_callable($by) AND $by($value, $key) === false) {
                continue;
            }
            $s->push($value, $key);
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

    public function reverse() {
        return array_reverse($this->asArray());
    }

    /**
     * @brief is the needle in the array
     *
     * @param $needle value in the data array
     * @return bool of result
     */
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

    public function sort($func) {
        usort($this->_data, $func);
        return $this;
    }

    /**
     * @brief does a value exist
     *
     * @param $name name of value
     * @return bool if it exists
     */
    public function exists($name) {
        if (stripos($name,'(')!==false) { return true;}
        return $this->value($name, -1) !== -1;
    }

    /**
     * @brief get an item by index
     *
     * @param $idx index of item
     * @return item value
     */
    public function item($idx) {
        if ($idx === 'first') {
            $idx = key(array_slice($this->_data, 0, 1));
        }
        else if ($idx === 'last') {
            $idx = key(array_slice($this->_data, -1));
        }
        return $this->get($idx);
    }

    /**
     * @brief set a value at index
     *
     * @param $offset offset value to set
     * @param $value value
     * @return self
     */
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->_data[] = $value;
        } else {
            $this->_data[$offset] = $value;
        }
        return $this;
    }

    /**
     * @brief check if an offset exists
     *
     * @param $offset offset name
     * @return bool if offset exists
     */
    public function offsetExists($offset) {
        return isset($this->_data[$offset]);
    }

    /**
     * @brief unset an offset
     *
     * @param $offset offset name
     * @return self
     */
    public function offsetUnset($offset) {
        unset($this->_data[$offset]);
        return $this;
    }

    /**
     * @brief get an offset value
     *
     * @param $offset offset name
     * @return value
     */
    public function offsetGet($offset) {
        return isset($this->_data[$offset]) ? $this->get($offset) : null;
    }

    /**
     * @brief rewind array pointer
     *
     * @return self
     */
    function rewind() {
        reset($this->_data);
        return $this;
    }

    /**
     * @brief current array pointer
     *
     * @return self
     */
    function current() {
        $var = current($this->_data);
        return (is_array($var) ? b::bucket($var) : $var);
    }

    /**
     * @brief array key pointer
     *
     * @return key
     */
    function key() {
          $var = key($this->_data);
        return $var;
    }

    /**
     * @brief advance array pointer
     *
     * @return current value
     */
    function next() {
        $var = next($this->_data);
        return $var;
    }

    /**
     * @brief is the current array pointer valid
     *
     * @return current value
     */
    function valid() {
        $var = $this->current() !== false;
        return $var;
    }

    /**
     * @brief get count of data
     *
     * @return count
     */
    function count() {
        return count($this->_data);
    }

}