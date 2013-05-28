<?php

namespace bolt\dao;
use \b;

interface iItem {}

abstract class item implements iItem {

    // private
    private $_guid; /// guid for unique objects
    private $_loaded = false; // has data been loaded
    private $_struct = array(); /// item struct
    private $_traits = array(); /// traits
    private $_traitInstance = array('\bolt\dao\traitStorage' => false);
    private $_data = false;

    // traits
    protected $traits = array();

    ////////////////////////////////////////////////////////////////////
    /// @brief get the struct of the item
    ///
    /// @return array of struct
    ////////////////////////////////////////////////////////////////////
    abstract public function getStruct();

    // find
    public function find() {return $this;}

    ////////////////////////////////////////////////////////////////////
    /// @brief constrcut a dao item
    ///
    /// @param $data array of data
    /// @return void
    ////////////////////////////////////////////////////////////////////
    public function __construct($data=array()) {
        $this->_guid = uniqid();    // guid to compare objects

        // bucket
        $this->_data = b::bucket($data);

        // set a struct
        $this->_struct = $this->getStruct();

        // traits
        if (count($this->traits) > 0) {
            $this->addTrait($this->traits);
        }

        // loop through our struct and
        // see if theres any class traits
        foreach ($this->_struct as $key => $info) {
            if (p('type', false, $info) == 'dao') {
                $this->_traits["get{$key}"] = array(
                    '\bolt\dao\traitStorage',
                    $info['class'],
                    (array_key_exists('method', $info) ? $info['method'] : 'find'),
                    (array_key_exists('args', $info) ? $info['args'] : array()),
                );
            }
        }

    }

    ////////////////////////////////////////////////////////////////////
    /// @brief has this item been loaded
    ///
    /// @return bool if item is loaded
    ////////////////////////////////////////////////////////////////////
    public function loaded() {
        return $this->_loaded;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC get a value
    ///
    /// @param $name name of value
    /// @see getValue()
    /// @return array of traits
    ////////////////////////////////////////////////////////////////////
    public function __get($name) {
        return $this->get($name);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC set a value
    ///
    /// @param $name name of value
    /// @param $value value
    /// @see setValue()
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function __set($name, $value) {
        return $this->set($name, $value);
    }

    public function __toString() {
        return $this->_data->__toString();
    }

    public function __isset($name) {
        return $this->get($name);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC
    ///
    /// @return array of traits
    ////////////////////////////////////////////////////////////////////
    public function __call($name, $args) {
        if (method_exists($this, $name)) {
            return call_user_func_array(array($this, $name), $args);
        }
        else if (array_key_exists(strtolower($name), $this->_traits)) {
            return $this->callTrait($name, $args);
        }
        else if (method_exists($this->_data, $name)) {
            return call_user_func_array(array($this->_data, $name), $args);
        }
        return false;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief returns a dao results array
    ///
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function asArray() {
        return $this->_data->asArray();
    }
    public function isEmpty() {
        return $this->_loaded;
    }


    ////////////////////////////////////////////////////////////////////
    /// @brief return a value
    ///
    /// @param $name name of value
    /// @param $default default return if value undefined
    /// @params $useTraits try to get the value from a trait
    /// @see bucket::get()
    /// @return value
    ////////////////////////////////////////////////////////////////////
    public function get($name, $default=false, $useTraits=true) {
        $getName = "get{$name}"; $value = false;

        // default value
        if (array_key_exists($name, $this->_struct) AND isset($this->_struct[$name]['default'])) {
            $default = $this->_struct[$name]['default'];
        }

        // find it
        if ($name !== 'value' AND method_exists($this, $getName)) {
            $value = call_user_func(array($this, $getName));
        }
        else if ($useTraits !== false AND array_key_exists(strtolower($getName), $this->_traits)) {
            $value = $this->callTrait($getName);
        }
        else {
            $value = $this->_data->get($name, $default);
        }

        // cast as something special
        if (array_key_exists($name, $this->_struct) AND isset($this->_struct[$name]['cast'])) {

            // is it a string
            if (is_a($value, '\bolt\bucket\bString')) {
                $value = $value->cast($this->_struct[$name]['cast']);
            }
            else {
                settype($value, $this->_struct[$name]['cast']);
            }
        }

        if (!is_object($value)) { $value = b::bucket($value); }

        return $value;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get a string value
    ///
    /// @param $values array of values
    /// @param $default default value is none is returned
    /// @param $useTraits use traits
    /// @return mixed value
    ////////////////////////////////////////////////////////////////////
    public function getValue($name, $default=false, $useTraits=true) {
        $v = $this->get($name, $default, $useTraits);
        return $v->value;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set a value
    ///
    /// @param $name name of value
    /// @param $default default return if value undefined
    /// @see bucket::set()
    /// @return sefl
    ////////////////////////////////////////////////////////////////////
    public function set($name, $value=false, $useTraits = true) {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->set($k, $v);
            }
            return $this;
        }
        $setName = "set{$name}";

        // cast as something special
        if (array_key_exists($name, $this->_struct) AND isset($this->_struct[$name]['cast'])) {
            if (is_a($value, '\bolt\bucket\bString')) {
                $value = $value->cast($this->_struct[$name]['cast']);
            }
            else {
                settype($value, $this->_struct[$name]['cast']);
            }
        }

        // find it
        if (method_exists($this, $setName)) {
            $value = call_user_func(array($this, $setName), $value);
        }
        else if ($useTraits AND array_key_exists(strtolower($setName), $this->_traits)) {
            $value = $this->callTrait($setName, array($value));
        }

        // set it
        $this->_data->set($name, $value);

        $this->_loaded = true;
        return $this;
    }


    ////////////////////////////////////////////////////////////////////
    /// @brief set an array of data
    ///
    /// @param $values array of values
    /// @see setValue
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setValue($name, $value) {
        $this->set($name, $value);
        return $this;
    }


    ////////////////////////////////////////////////////////////////////
    /// @brief noramize the data array with struct
    ///
    /// @return array of values
    ////////////////////////////////////////////////////////////////////
    public function normalize() {
        $data = $this->_data->getData();


        // loop through the struct
        // and try to normalize the data
        foreach ($this->_struct as $key => $info) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $this->get($key);
            }

            // name of normalize function
            $name = strtolower("normalize{$key}");

            // check to see if we have a normalization trai
            if (array_key_exists($name, $this->_traits)) {
                $data[$key] = $this->callTrait($name);

            }

            // if we have a bucket or bstring convert
            if (is_a($data[$key], '\bolt\bucket')) {
                $data[$key] = $data[$key]->asArray();
            }

            if (is_a($data[$key], '\bolt\bucket\bString')) {
                $data[$key] = $data[$key]->value;
            }

            // make sure the tair conforms to any cast
            if (isset($info['cast'])) {
                if ($info['cast'] == 'array' AND !is_array($data[$key])) {
                    $data[$key] = array();
                }
                else if ($info['cast'] != 'array') {
                    settype($data[$key], $info['cast']);
                }
            }

            // default value
            if (!$data[$key] AND isset($info['default'])) {
                $data[$key] = $info['default'];
            }

        }

        // return our normalize array
        return $data;

    }

    ////////////////////////////////////////////////////////////////////
    /// @brief add trait
    ///
    /// @param $class class name or method name
    /// @param $closure callback
    /// @param $args arguments to pass to callback
    /// @return self
    ////////////////////////////////////////////////////////////////////
    protected function addTrait($class, $cb=false, $args=array()) {
        if ($cb AND is_a($cb, 'Closure')) {
            $this->_traits[strtolower($class)] = array(
                    $cb,
                    $args
                );
        }
        else {
            $classes = (is_array($class) ? $class : array($class));
            foreach ($classes as $class) {
                $this->_traitInstance[$class] = false;
                foreach (get_class_methods($class) as $method) {
                    $this->_traits[strtolower($method)] = array($class, $method);
                }
            }
        }
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief return the current traits
    ///
    /// @return array of traits
    ////////////////////////////////////////////////////////////////////
    public function getTraits() {
        return $this->_traits;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief return an array of trait instances
    ///
    /// @return array of traits instances
    ////////////////////////////////////////////////////////////////////
    public function getTraitInstances() {
        return $this->_traitInstance;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief return the current traits
    ///
    /// @return array of traits
    ////////////////////////////////////////////////////////////////////
    protected function callTrait($name, $args=array()) {
        $t = $this->_traits[strtolower($name)];
        $func = false; $_args = array();

        var_dump($name);

        // is trait callable
        if (is_callable($t[0])) {
            if (isset($t[1])) {
                foreach ($t[1] as $key) {
                    if ($key{0} == '$') {
                        $_args[] = $this->getValue(substr($key, 1), false, false);
                    }
                    else {
                        $_args[] = $key;
                    }
                }
            }
            $func = $t[0];
        }
        else {

            // create a new trait class
            if (!$this->_traitInstance[$t[0]]) {
                $this->_traitInstance[$t[0]] = new $t[0]($this);
            }

            // instance
            $i = $this->_traitInstance[$t[0]];

            // trait storage
            if (is_a($i, '\bolt\dao\traitStorage')) {
                if (!$i->hasInstance($name)) {
                    foreach ($t[3] as $key) {
                        if ($key{0} == '$') {
                            $key = substr($key, 1);
                            // $_args[] = $this->getValue($key, false, false);
                        }
                        else {
                            $_args[] = $key;
                        }
                    }
                    $i->createInstance($name, $t[1], $t[2], $_args);
                }

                // reset
                $func = array($i, 'getInstance');
                $_args = array($name);

            }
            else {

                // relect on the class and see what data they might want
                $ref = new \ReflectionMethod($i, $t[1]);

                // params
                $params = $ref->getParameters();



                // loop
                if (count($params) > 0) {
                    foreach ($params as $parm) {
                        $_args[] = $this->_data->getValue($param->name, $param->getDefaultValue(), false);
                    }
                }

                // this function
                $func = array($i, $t[1]);

            }

        }

        // give back with args
        return call_user_func_array($func, $_args);

    }

}

// trait storage
class traitStorage {

    private $_instance = array();

    public function hasInstance($name) {
        return array_key_exists($name, $this->_instance);
    }

    public function createInstance($name, $class, $method, $args) {
        $i = b::dao($class);
        $this->_instance[$name] = call_user_func_array(array($i, $method), $args);
    }

    public function getInstance($name) {
        return $this->_instance[$name];
    }

}