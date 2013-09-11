<?php

namespace bolt\model;
use \b;

interface iModelBase {}

abstract class base implements iModelBase {

    // private
    private $_guid; /// guid for unique objects
    private $_loaded = false; // has data been loaded
    private $_struct = array(); /// item struct
    private $_source = false; // source holder
    private $_attr = array(); // attribute classes

    // traits
    protected $traits = array();

    ////////////////////////////////////////////////////////////////////
    /// @brief constrcut a model item
    ///
    /// @param $data array of data
    /// @return void
    ////////////////////////////////////////////////////////////////////
    public function __construct($data=array()) {
        $this->_guid = uniqid();    // guid to compare objects

        // get any defined attributes
        $classes = b::getDefinedSubClasses('\bolt\model\attr');

        // loop
        foreach ($classes as $class) {
            $name = ($class->hasConstant('NAME') ? $class->getConstant('NAME') : $class->getShortName());
            $this->_attr[$name] = $class->name;
        }


        // bucket
        $this->_data = b::bucket($data);

        // traits
        if (count($this->traits) > 0) {
            $this->addTrait($this->traits);
        }

        // set a struct
        $this->setStruct($this->getStruct());

        // shorthad source
        $this->_source = b::source();

    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get the struct of the item
    ///
    /// @return array of struct
    ////////////////////////////////////////////////////////////////////
    abstract public function getStruct();

    public function setStruct($struct) {
        if (!is_array($struct)) {return $this;}

        $this->_struct = $struct;

        // loop through our struct and
        // see if theres any class traits
        foreach ($this->_struct as $key => $info) {
            $type = (array_key_exists('type', $info) ? $info['type'] : 'base');

            // no attr for type
            if (!array_key_exists($type, $this->_attr)) {
                $type = 'base';
            }

            // create a new attribute holder
            // for this attr
            $this->_struct[$key]['_attr'] = new $this->_attr[$type]($key, $info, $this);


        }

        return $this;
    }

    // find
    public function find($query, $args=array()) {

        // send to source
        $resp = $this->_source->model($this, 'find', $query, $args);
        $items = array();

        // already a response
        if (is_a($resp, 'bolt\model\result')) {
            return $resp;
        }

        // what am i
        $class = get_called_class();

        foreach ($resp as $item) {
            $items[] = (new $class())->set($item->asArray());
        }

        // give bacl
        return new result($class, $items);

    }


    public function findOne($query, $args=array()) {
        $resp = $this->_source->model($this, 'find', $query, $args);

        // return
        if ($resp->count() > 0) {
            $this->set($resp->item(0)->asArray());
            $this->_loaded = true;
        }

        // me
        return $this;

    }

    public function findOneBy($field, $value, $args=array()) {
        $resp = $this->_source->model($this, 'find', array($field => $value), $args);

        // return
        if ($resp->count() > 0) {
            $this->set($resp->asArray());
            $this->_loaded = true;
        }

        // me
        return $this;

    }


    public function findById($value, $args=array()) {

        $resp = $this->_source->model($this, 'findById', $value, $args);

        // return
        if ($resp->count() > 0) {
            $this->set($resp->asArray());
            $this->_loaded = true;
        }

        // me
        return $this;

    }

    public function count($query, $args=array()) {
        return $this->_source->model($this, 'count', $query, $args);
    }

    public function save($data=array(), $args=array()) {

        if ($data) {
            $this->set($data);
        }

        // normalize
        $data = $this->normalize();

        // insert or update
        $key = $this->value($this->getPrimaryKey());


        if ($key === false) {
            unset($data[$this->getPrimaryKey()]);
            $resp = $this->_source->model($this, 'insert', $data, $args);
        }
        else {
            $resp = $this->_source->model($this, 'update', $key, $data, $args);
        }

        // set data
        $this->set($resp->asArray());
        $this->_loaded = true;

        //
        return $this;

    }

    public function getPrimaryKey() {
        foreach ($this->_struct as $name => $field) {
            if (isset($field['primary']) AND $field['primary'] == 'true') {
                return $name;
            }
        }
    }


    ////////////////////////////////////////////////////////////////////
    /// @brief has this item been loaded
    ///
    /// @return bool if item is loaded
    ////////////////////////////////////////////////////////////////////
    public function loaded($set=null) {
        if ($set !== null) {$this->_loaded = $set;}
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
        return $this->asArray();
    }

    public function __isset($name) {
        return array_key_exists($name, $this->_struct);
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
        return false;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief returns a model results array
    ///
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function asArray() {
        $data = array();
        foreach ($this->_struct as $key => $info) {
            $data[$key] = $info['_attr']->value();
        }
        return $data;
    }
    public function isEmpty() {
        return $this->_loaded;
    }


    ////////////////////////////////////////////////////////////////////
    /// @brief return a value
    ///
    /// @param $name name of value
    /// @param $default default return if value undefined
    /// @params $useAttr try to get the value from a attr
    /// @see bucket::get()
    /// @return value
    ////////////////////////////////////////////////////////////////////
    public function get($name, $default=false) {
        $value = $default;

        // always try the method first
        if (array_key_exists($name, $this->_struct)) {
            $value = $this->_struct[$name]['_attr']->call('get');
        }
        else if (method_exists($this, "get{$name}")) {
            $value = call_user_func_array(array($this, "get{$name}"), array());
        }

        // buketize it
        if (!\bolt\bucket::isBucket($value)) {
            $value = \bolt\bucket::byType($value, $name, $this);
        }

        return $value;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get a string value
    ///
    /// @param $values array of values
    /// @param $default default value is none is returned
    /// @return mixed value
    ////////////////////////////////////////////////////////////////////
    public function value($name) {
        $value = false;

        // always try the method first
        if (array_key_exists($name, $this->_struct)) {
            $value = $this->_struct[$name]['_attr']->call('value');
        }

        return $value;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set a value
    ///
    /// @param $name name of value
    /// @param $default default return if value undefined
    /// @see bucket::set()
    /// @return sefl
    ////////////////////////////////////////////////////////////////////
    public function set($name, $value=false) {

        // if name is an array, do that instean
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->set($k, $v);
            }
            return $this;
        }

        if (!is_string($name)) {return $this;}

        // find it
        if (array_key_exists($name, $this->_struct)) {
            $this->_struct[$name]['_attr']->call('set', array($value));
        }

        // me
        return $this;
    }


    ////////////////////////////////////////////////////////////////////
    /// @brief noramize the data array with struct
    ///
    /// @return array of values
    ////////////////////////////////////////////////////////////////////
    public function normalize() {
        $data = $this->_data->asArray();
        $done = array();

        // loop through the struct
        // and try to normalize the data
        foreach ($this->_struct as $key => $info) {

            // check to see if we have a normalization trai
            $data[$key] = $this->_struct[$key]['_attr']->call('normalize');

            // already done
            $done[] = $key;

        }


        // return our normalize array
        return $data;

    }

    // validate
    public function validate() {

        $data = $this->_data->asArray();
        $done = array();

        // loop through the struct
        // and try to normalize the data
        foreach ($this->_struct as $key => $info) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $this->get($key);
            }

            // name of normalize function
            $name = strtolower("normalize{$key}");

            // check to see if we have a normalization trai
            if (method_exists($this, $name)) {
                $data[$key] = call_user_func_array(array($this, $name), $data[$key]);
            }
            else {
                $data[$key] = $this->_struct[$key]->normalize($data[$key]);
            }

            // already done
            $done[] = $key;

        }

    }

    public function fields($keys) {
        $resp = array();
        foreach ($keys as $name) {
            $resp[str_replace('.', '_', $name)] = $this->get($name);
        }
        return \bolt\bucket::byType($resp);
    }

    public function filter($by) {
        $b = \bolt\bucket::byType($this->asArray());
        return $b->filter($by);
    }

}

