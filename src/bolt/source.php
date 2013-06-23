<?php

namespace bolt;
use \b as b;

// plugin
b::plug('source', '\bolt\source');

// source
class source extends \bolt\plugin\singleton {

    // events
    public static $pluginEvents = array(
        'ready' => array('func' => 'onReady')
    );


    // main adapter adapter
    private $_adapter = false;
    private $_adapters = array();
    private $_instances = array();

    // construct
    public function __construct() {

    }

    // when ready
    public function onReady() {

        // find everything that extends source
        $classes = b::getDefinedSubClasses('\bolt\source\base');

        // loop and set them up
        foreach ($classes as $adapter)  {
            $this->_adapters[$adapter->getConstant('NAME')] = $adapter->name;
        }

        // see if there's a any source config
        $source = b::settings("project")->value('source');

        // nothing means stpo
        if (!$source OR !isset($source['adapter'])) {
            return;
        }

        $this->_adapter = $this->factory($source['adapter'], $source);

    }

    public function adapter($name, $config=false) {
        if (!$this->_instances[$name]) {
            $this->_instances[$name] = $this->factory($name, $config);
        }
        return $this->_instances[$name];
    }

    public function factory($name, $config) {
        return new $this->_adapters[$name]($config);
    }

    public function __call($name, $args) {
        if (method_exists($this->_adapter, $name)) {
            return call_user_func_array(array($this->_adapter, $name), $args);
        }
    }

}