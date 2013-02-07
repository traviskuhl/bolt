<?php

namespace bolt\dao;

class stack implements \Iterator, \ArrayAccess {

    // map
    private $_items = array();  /// array of items
    private $_meta = false;     /// meta data for this stack
    private $_loaded = false;   /// have we tried to call a push
    private $_key = false;       /// key for id
    private $_class = '\bolt\dao\item'; // type of object we store

    // pager stuff
    private $_total = 0;        /// total number of possible items
    private $_limit = 0;        /// limit of items
    private $_offset = 0;       /// offset items

    // create
    public static function create($items, $class='\bolt\dao\item', $key = false) {
        $stack = new stack($classm, $key);
        return $stack->setItems($items);
    }

    // construct
    public function __construct($class='\bolt\dao\item', $key=false) {
        $this->_class = $class;
        $this->_key = $key;
    }

    public function loaded() {
        return $this->_loaded;
    }

    public function setItems($items) {
        foreach ($items as $key => $item) {
            $this->push($items, $key);
        }
        return $this;
    }

    public function setTotal($t) {
        $this->_total = $t;
        return $this;
    }

    public function setLimit($l) {
        $this->_limit = $l;
        return $this;
    }

    public function setOffset($o) {
        $this->_offset = $o;
        return $this;
    }

    public function setMeta($o) {
        $this->_meta = (is_array($o) ? new item($o) : $o);
        return $this;
    }

    public function getMeta() {
        return $this->_meta;
    }

    public function getOffset() {
        return (int)$this->_offset;
    }

    public function getLimit() {
        return (int)$this->_limit;
    }

    public function getTotal() {
        return (int)$this->_total;
    }

    public function getPage() {
        return ($this->_offset ? floor($this->_offset / $this->_limit) + 1 : 1);
    }

    public function getPages() {
        return ($this->_limit ? ceil($this->_total / $this->_limit) : 0);
    }

    public function __call($name, $args) {
        return ($this->_meta ? call_user_func_array(array($this->_meta, $name), $args) : false);
    }

    public function __get($name) {
        return ($this->_meta ? $this->_meta->__get($name) : false);
    }

    public function __set($name, $value) {
        return ($this->_meta ? $this->_meta->$name = $val : false);
    }

    // push
    public function push($item, $key=false) {

        // if item is an aray
        // turn it into an object
        if (is_array($item)) {
            $item = new $this->_class($item);
        }

        // no key
        if ($key===false AND $this->_key) {
            $key = $item->$key;
        }

        // loaded
        $this->_loaded = true;

        // push
        $this->_items[$key] = $item;

        // chainable
        return $this;

    }

    public function clear() {
        $this->_loaded = false;
        $this->_items = array();
    }

    // add
    public function add($item, $key=false) {
        $this->push($item, $key);
    }

    // item
    public function item($idx=0) {

        // what up
        switch($idx) {

            // first item
            case 'first':
                $idx = 0;
                break;

            // last item
            case 'last':
                $idx = array_shift($this->_map);

                break;

            // else
            default:
                if (array_key_exists($idx, $this->_map)) {
                    $idx = $this->_map[$idx];
                }
        };

        // nope
        if ($this->offsetExists($idx)) {
            return $this->offsetGet($idx);
        }
        else {
            return false;
        }

    }

    /////////////////////////////////////////////////
    /// @brief reset pointer to first item in set
    ///
    /// @return void
    /////////////////////////////////////////////////
    public function rewind() {
        reset($this->_items);
    }


    /////////////////////////////////////////////////
    /// @brief get the current item pointer
    ///
    /// @return value of current pointer item
    /////////////////////////////////////////////////
    public function current() {
        $var = current($this->_items);
        return $var;
    }


    /////////////////////////////////////////////////
    /// @brief key value of current pointer item
    ///
    /// @return value of pointer item
    /////////////////////////////////////////////////
    public function key() {
        $var = key($this->_items);
        return $var;
    }


    /////////////////////////////////////////////////
    /// @brief go to next item in the set
    ///
    /// @return value of next item in set
    /////////////////////////////////////////////////
    public function next() {
        $var = next($this->_items);
        return $var;
    }


    /////////////////////////////////////////////////
    /// @brief check if the current value is valid
    ///
    /// @return bool if current value is valid
    /////////////////////////////////////////////////
    public function valid() {
        $var = $this->current() !== false;
        return $var;
    }

    public function offsetSet($offset, $value) {
        $this->_items[$offset] = $value;
    }
    public function offsetExists($offset) {
        return isset($this->_items[$offset]);
    }
    public function offsetUnset($offset) {
        unset($this->_items[$offset]);
    }
    public function offsetGet($offset) {
        return isset($this->_items[$offset]) ? $this->objectify($this->_items[$offset]) : null;
    }





    public function count() {
        return count($this->_items);
    }


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

        // gives back a new stack
        $s = new stack();

            // loop
            foreach (array_unique($resp) as $i) {
                $s->push($i);
            }

        // give bacl
        return $s;

    }

    public function map($cb) {
        $resp = array();

        // cb is a string
        if(is_string($cb)) {
            if ($cb{0} == '$') {
                $key = substr($cb,1);
                $cb = function($item) use ($key) {
                    return $item->$key;
                };
            }
        }

        foreach ($this as $item) {
            if (($r = $cb($item)) !== false AND !is_array($r)) {
               $resp[] = $r;
            }
        }

        // gives back a new stack
        $s = new stack();

            // loop
            foreach ($resp as $i) {
                $s->push($i);
            }

        // give bacl
        return $s;

    }

    public function filter($cb) {
        $s = new stack();

        foreach ($this as $item) {
            if ($cb($item) !== false) {
                $s->push($item);
            }
        }

        // give bacl
        return $s;

    }

    public function sort($cb, $sort=false) {
        $s = new stack();

        // array
        $array = array();

        foreach ($this as $key => $item) {
            $array[$key] = (is_callable($cb) ? $cb($item) : $item->$cb);
        }

        // sort it
        if (is_callable($sort)) {
            uasort($array, $sort);
        }
        else if ($sort) {
            arsort($array);
        }
        else {
            asort($array);
        }

        foreach ($array as $key => $item) {
            $s->push($this->item($key));
        }

        // give bacl
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

    public function asArray() {
        $array = array();
        foreach ($this as $item) {
            if (is_object($item) AND method_exists($item, 'asArray')) {
                $array[] = $item->asArray();
            }
            else {
                $array[] = $item;
            }
        }
        return $array;
    }

}

