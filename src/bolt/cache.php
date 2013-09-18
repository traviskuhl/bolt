<?php

namespace bolt;
use \b as b;

// plugin
b::plug('cache', '\bolt\cachce');

// cachce
class cachce extends \bolt\plugin\singleton {

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

        // find everything that extends cache
        $classes = b::getDefinedSubClasses('\bolt\cache\base');

        // loop and set them up
        foreach ($classes as $adapter)  {
            $this->_adapters[$adapter->getConstant('NAME')] = $adapter->name;
        }

    }

    public function onRun() {

        // see if there's a any cache config
        $cache = b::settings()->value('project.cache');

        // nothing means stpo
        if (!$cache OR !isset($cache['adapter'])) {
            return;
        }

        $this->_adapter = $this->factory($cache['adapter'], $cache);

    }

    public function setDefaultAdapter($adapter, $cfg=array()) {

        if (b::isInterfaceOf($adapter, '\bolt\cache\bCache')) {
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
        return call_user_func_array(array($this->_adapter, $name), $args);
    }

}