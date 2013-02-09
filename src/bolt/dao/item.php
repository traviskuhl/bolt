<?php

namespace bolt\dao;
use \b;

abstract class item extends \bolt\bucket {

    // private
    private $_guid; /// guid for unique objects
    private $_struct = array(); /// item struct
    private $_traits = array(); /// traits
    private $_traitInstance = array('\bolt\dao\traitStorage' => false);

    // traits
    protected $traits = array();

    ////////////////////////////////////////////////////////////////////
    /// @brief get the struct of the item
    ///
    /// @return array of struct
    ////////////////////////////////////////////////////////////////////
    abstract public function getStruct();

    ////////////////////////////////////////////////////////////////////
    /// @brief add traits
    ///
    /// @return self
    ////////////////////////////////////////////////////////////////////
    protected function addTrait($classes) {
        if (!is_array($classes)) {$classes = array($classes); }
        foreach ($classes as $class) {
            $this->_traitInstance[$class] = false;
            foreach (get_class_methods($class) as $method) {
                $this->_traits[strtolower($method)] = array($class, $method);
            }
        }
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief constrcut a dao item
    ///
    /// @param $data array of data
    /// @return void
    ////////////////////////////////////////////////////////////////////
    public function __construct($data=array()) {
        $this->_guid = uniqid();    // guid to compare objects
        $this->setData($data);      // set some data

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
                    p('method', 'get', $info),
                    p_raw('args', array(), $info)
                );
            }
        }

    }

    public function __get($name) {
        return $this->value($name);
    }

    public function value($name, $default=false) {
        $getName = "get{$name}"; $value = false;

        // default value
        if (array_key_exists($name, $this->_struct) AND isset($this->_struct[$name]['default'])) {
            $default = $this->_struct[$name]['default'];
        }

        // find it
        if (method_exists($this, $getName)) {
            $value = call_user_func(array($this, $getName));
        }
        else if (array_key_exists(strtolower($getName), $this->_traits)) {
            $value = $this->callTrait($getName);
        }
        else {
            $value = parent::get($name, $default);
        }

        // cast as something special
        if (array_key_exists($name, $this->_struct) AND isset($this->_struct[$name]['cast'])) {

            // is it a string
            if (is_a($value, '\bolt\bucket\bString')) {
                $value = $value->cast($this->_struct[$name]['cast']);
            }
            else {
                $value = settype($value, $this->_struct[$name]['cast']);
            }
        }
        return $value;
    }



    public function __call($name, $args) {
        if (method_exists($this, $name)) {
            return call_user_func(array($this, $name));
        }
        else if (array_key_exists(strtolower($name), $this->_traits)) {
            return $this->callTrait($name, $args);
        }
        return false;
    }

    public function normalize() {



    }


    public function callTrait($name, $args=array()) {
        $t = $this->_traits[strtolower($name)];
        $func = false; $_args = array();

        // is trait callable
        if (is_callable($t[0])) {
            if (isset($t[1])) {
                foreach ($t[1] as $key) {
                    if ($key{0} == '$') {
                        $_args[] = $this->get(substr($key, 1));
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
                            $_args[] = $this->value(substr($key, 1));
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
                        $_args[] = $this->value($param->name, $param->getDefaultValue());
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