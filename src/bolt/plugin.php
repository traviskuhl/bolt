<?php

namespace bolt;
use \b;

abstract class plugin {

    // plugins
    private $_plugin = array();
    private $_instance = array();
    private $_fallback = array();
    private $_events = array();

    // let's construct
    public function __construct($fallback=array()) {
        $this->setFallbacks($fallback);
    }
    public function __destruct() {
        $this->fire('destruct');
    }

    // plugin
    public function getPlugins() {
        return $this->_plugin;
    }

    // fallback
    public function setFallbacks($classes) {
        if (is_string($classes)) {
            $classes = array($classes);
        }
        return $this->_fallback = array_merge($this->_fallback, $classes);
    }

    // get fallcacks
    public function getFallbacks() {
        return $this->_fallback;
    }

    // call something
    public function __call($name, $args) {
        return $this->call($name, $args);
    }

    // on
    public function on($name, $callback, $args=array()) {
        if (!array_key_exists($name, $this->_events)){ $this->_events[$name] = array(); }
        $eid = uniqid();
        $this->_events[$name] = array('callback' => $callback, 'args' => $args, 'eid' => $eid);
        return $eid;
    }

    public function fire($name, $args=array()) {
        if (array_key_exists($name, $this->_events)) {
            foreach ($this->_events as $event) {
                if (is_callable($event['callback'])) {
                    $args['args'] = $event['args'];
                    $args['eid'] = $event['eid'];
                    $args['this'] = $this;
                    call_user_func($event['callback'], $args);
                }
            }
        }
    }

    ////////////////////////////////////////////////////////////
    /// @brief call one of our plugins
    ////////////////////////////////////////////////////////////
    public function call($name, $args=array()) {

        b::log("b::plugin::call with name '%s'", array($name));

        // func
        $method = false;

        // see if this needs to be routed
        // to another
        if (stripos($name, '.') !== false) {

            // split into parts
            $parts = explode('.', $name);

            // first part
            $name = array_shift($parts);

            // set the rest of parts as $args
            if (array_key_exists($name, $this->_plugin)) {
                b::log("b::plugin::call sent to plugin '%s'::call", array($name));
                return call_user_func(array($this->_plugin[$name], 'call'), array_merge(array(implode('.', $parts)), $args));
            }
            else {
                b::log("b::plugin::call unknown namespaced plugin %s", array($name));
                return false;
            }

        }

        // do we not have a plugin for this
        if (!array_key_exists($name, $this->_plugin)) {

            // loop through our fallbacks
            foreach ($this->_fallback as $class) {
                if (method_exists($class, $name)) {
                    b::log("b::plugin::call sent to fallback '%s::%s'", array($class, $name));
                    return call_user_func_array(array($class, $name), $args);
                }
            }

            // no fallback
            b::log("b::plugin::call unknown plugin '%s' (no fallback)", array($name));

            // we go nothing
            return false;

        }

        b::log("b::plugin::call found plugin '%s'", array($name));

        // get it
        $plug = $this->_plugin[$name];

        // figure out if there's a function to direct to
        if (is_string($plug) AND strpos($plug, '::')!== false) {

            // get the orig plugin name
            list($name, $method) = explode('::', $plug);

            // reset plug
            $plug = $this->_plugin[$name];

        }

        // is plug callable
        if (is_callable($plug)) {
            b::log("b::plugin::call - plugin is callable", array($name));
            return call_user_func_array($plug, $args);
        }

        // ask the class what it is
        if ($plug::$TYPE == 'factory') {
            b::log("b::plugin::call - plugin is a factory. sending to '%s::factory'", array($name));
            return call_user_func_array(array($plug, "factory"), $args);
        }

        // singleton
        else if ($plug::$TYPE == 'singleton') {

            // if we don't have an instance
            if (!array_key_exists($name, $this->_instance)) {
                $this->_instance[$name] = new $plug($args);
            }

            // instance
            $i = $this->_instance[$name];

            // if instance is another plugin
            // we can chain our call method
            if (get_parent_class($i) == 'bolt\plugin' AND isset($args[0]) AND is_string($args[0])) {
                b::log("b::plugin::call - sent to plugin '%s::call'", array($name));
                return call_user_func(array($i, 'call'), $name, $args);
            }

            // is it a string
            if ($method AND method_exists($i, $method)) {
                b::log("b::plugin::call - sent to plugin '%s::%s'", array($name, $method));
                return call_user_func_array(array($i, $method), $args);
            }
            else if (isset($args[0]) AND is_string($args[0]) AND method_exists($i, $args[0]) ){
                b::log("b::plugin::call - sent to plugin '%s::%s", array($name, $args[0]));
                return call_user_func_array(array($i, array_shift($args)), $args);
            }
            else if (method_exists($i, "_default")) {
                b::log("b::plugin::call - sent to plugin '%s::_default'", array($name));
                return call_user_func_array(array($i, "_default"), $args);
            }
            else {
                b::log("b::plugin::call - unknown method. returned instance", array($name));
                return $i;
            }


        }

    }

    ////////////////////////////////////////////////////////////
    /// @brief plugin to bolt
    ////////////////////////////////////////////////////////////
    public function plug($name, $class=false) {

        // is it an array
        if (is_array($name)) {
            foreach ($name as $n => $c) {
                $this->_plugin[$n] = $c;
            }
        }

        // just one
        else {
            $this->_plugin[$name] = $class;
        }

        // good
        return true;

    }


}