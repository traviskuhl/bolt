<?php

namespace bolt\model;
use \b;

interface iModelBase {}

abstract class base implements iModelBase {

    // private
    private $_guid; /// guid for unique objects
    private $_loaded = false; // has data been loaded
    private $_data = false;
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

            // create a new attribute holder
            // for this attr
            $this->_struct[$key]['_attr'] = new $this->_attr[$type]($key, $info, $this);


            // if (b::param('type', false, $info) == 'model') {
            //     $name = "get{$key}";
            //     if (array_key_exists($name, $this->_traits)) {continue;}

            //     $this->_traits[$name] = array(
            //         '\bolt\model\traitStorage',
            //         $info['model'],
            //         (array_key_exists('method', $info) ? $info['method'] : 'findById'),
            //         (array_key_exists('args', $info) ? $info['args'] : array('$'.$key)),
            //     );
            // }
            // if (b::param('children', false, $info)) {

            //     // instnace
            //     $i = (b::param('multiple', false, $info) ? new children() : new child());

            //     // setup with struct
            //     $i->setup($info['children']);

            //     // add the traits we need
            //     $this->_traits["get{$key}"] = array(array($i, '_get'), array('$'.$key));
            //     $this->_traits["normalize{$key}"] = array(array($i, '_normalize'), array('$'.$key));

            // }
        }

        return $this;
    }

    // find
    public function find($query, $args=array()) {

        // send to source
        $resp = $this->_source->query($this->table, $query, $args);
        $items = array();

        // what am i
        $class = get_called_class();

        foreach ($resp as $item) {
            $items[] = (new $class())->set($item->asArray());
        }

        // give bacl
        return new result($items);

    }


    public function findOne($field, $value, $args=array()) {

        $resp = $this->_source->query($this->table, array($field => $value), $args);

        // return
        if ($resp->count() > 0) {
            $this->set($resp->item('first')->asArray());
            $this->_loaded = true;
        }

        // me
        return $this;

    }

    public function findBy() {
        return call_user_func_array(array($this, 'findOne'), func_get_args());
    }

    public function findById($value, $args=array()) {
        return $this->findBy('id', $value, $args);
    }

    public function count($query, $args=array()) {
        return $this->_source->count($query, $args);
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
            $resp = $this->_source->insert($this->table, $data, $args);
        }
        else {
            $resp = $this->_source->update($this->table, $key, $data, $args);
        }

        // set data
        $this->set($resp[1]);
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
        else if (method_exists($this->_data, $name)) {
            return call_user_func_array(array($this->_data, $name), $args);
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
            $data[$key] = $this->get($key)->asArray();
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
    public function get($name, $default=false, $useAttr=true) {
        $getName = "get{$name}"; $value = $this->_data->get($name, $default);

        // always try the method first
        if ($useAttr AND method_exists($this, $getName)) {
            $value = call_user_func(array($this, $getName));
        }
        else if ($userAttr AND array_key_exists($name, $this->_struct)) {
            $value = $this->_struct[$name]['_attr']->get($value);
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
    /// @param $useAttr use attr
    /// @return mixed value
    ////////////////////////////////////////////////////////////////////
    public function value($name, $default=false, $useAttr=true) {
        $v = $this->get($name, $default, $useAttr);
        return $v->value();
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set a value
    ///
    /// @param $name name of value
    /// @param $default default return if value undefined
    /// @see bucket::set()
    /// @return sefl
    ////////////////////////////////////////////////////////////////////
    public function set($name, $value=false, $useAttr = true) {

        // if name is an array, do that instean
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->set($k, $v, $useAttr);
            }
            return $this;
        }

        // set name
        $setName = "set{$name}";

        // find it
        if ($useAttr AND method_exists($this, $setName)) {
            $value = call_user_func(array($this, $setName), $value);
        }
        else if ($useAttr AND array_key_exists($name, $this->_struct)) {
            $value = $this->_struct[$name]['_attr']->set($value);
        }

        // set it
        $this->_data->set($name, $value);

        // me
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
        $this->set($name, $value, false);
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

        // loop through data and try to normalize
        // anything that isn't in struct
        foreach ($data as $key => $value) {
            if (in_array($key, $done)) { continue; }
            $name = "normalize$key";

            // call it
            if (method_exists($this, $name)) {
                $data[$key] = call_user_func_array(array($this, $name), $data[$key]);
            }

            // if we have a bucket or bstring convert
            if (b::isInterfaceOf($data[$key], '\bolt\iBucket')) {
                $data[$key] = $data[$key]->value();
            }

            if (b::isInterfaceOf($data[$key], '\bolt\model\iModelBase')) {
                $data[$key] = $data[$key]->value($data[$key]->getPrimaryKey());
            }

        }

        // return our normalize array
        return $data;

    }

    public function fields($keys) {
        $resp = array();
        foreach ($keys as $name) {
            $resp[str_replace('.', '_', $name)] = $this->get($name);
        }
        return \bolt\bucket::byType($resp);
    }


}

class children extends result {
    private $_first = true;

    public function setup($struct) {
        $this
            ->setClass('\bolt\model\child')
            ->setStruct($struct);
    }
    public function _get($data) {
        if ($this->_first) {
            $this->setItems($data);
            $this->_first = false;
        }
        return $this;
    }
    public function _normalize($data) {
        if ($this->_first) {
            $this->setItems($data);
            $this->_first = false;
        }
        $items = array();
        foreach ($this as $key => $item) {
            $items[$key] = $item->normalize();
        }
        return $items;
    }

}

class child extends base {
    private $first = true;
    private $results = false;
    public function getStruct() { return array(); }

    private function setup($struct) {
        $this->setStruct($struct);
    }

    public function _get($data) {
        if ($this->first) {
            $this->set($data);
            $this->first = false;
        }
        return $this;
    }
    public function _normalize() {
        if ($this->first) {
            $this->set($data);
            $this->first = false;
        }
        return $this->normalize();
    }

}