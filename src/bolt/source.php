<?php

namespace bolt;
use \b as b;

// plugin
b::plug('source', '\bolt\source');

// source
class source extends \bolt\plugin\singleton {

    // events
    public static $pluginEvents = array(
        'ready' => array('func' => 'onReady'),
        'run' => array('func' => 'onRun')
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

    }

    public function onRun() {

        // see if there's a any source config
        $source = b::settings()->value('project.source');

        // nothing means stpo
        if (!$source OR !isset($source['adapter'])) {
            return;
        }

        $this->_adapter = $this->factory($source['adapter'], $source);

    }

    public function setDefaultAdapter($adapter, $cfg=array()) {

        if (b::isInterfaceOf($adapter, '\bolt\source\bSource')) {
            $this->_adapter = $adapter;
        }
        else {
            $this->_adapter = $this->factory($adapter, $cfg);
        }
    }

    public function addAdapter($class) {
        $this->_adapters[$class::NAME] = $class;
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