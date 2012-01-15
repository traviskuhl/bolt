<?php

// namespace me
namespace bolt\cache;
use \b as b;

// plug in memcache to bolt
b::plug('memcache', '\bolt\cache\memcache');

// plugin to instance source factory
b::cache()->plug('memcache', '\bolt\cache\memcachei');

// singleton class
class memcache extends \bolt\plugin\singleton {

    private $instance = false;

    public function __construct($args=array()) {      
        $this->instance = b::cache()->memcache(b::config()->memcache);
    }
    
    // call it
    public function __call($name, $args) {
    
        return call_user_func_array(array($this->instance, $name), $args);
    }

}

// instance class
class memcachei extends \bolt\plugin\factory {

    private $_ns = false;
    private $_cfg = array();
    private $_handle = false;
    
    public function __construct($cfg=array()) {
    
        // cfg is not an array or doesn't have any hosts
        if (!is_array($cfg) OR !isset($cfg['hosts'])) {
            return;
        }
    
        // globalize our config
        $this->_cfg = $cfg;
        
		// host
		$hosts = (is_string($cfg['hosts']) ? explode(',', $cfg['hosts']) : $cfg['hosts'] );        
        
            // no hosts in config we stop
            if (!$hosts OR count($hosts) == 0) {
                return;
            }            
    
		// base_ns
		// we add the host so that dev boxes
		// have their own cache namespace
		$this->_ns = p('ns', 'cache', $cfg).":";

        // pid
        $pid = p('pid', false, $cfg);

		// $mem
		$this->_handle = new \Memcached($pid); 

		// add servers
		$this->_handle->addServers(array_map(function($el){ return array($el, 11211, 1); }, $hosts));    
    
    }

    public function __get($name) {
        return $this->__call('get', func_get_args());
    }

    public function __set($name, $value) {
        return $this->__call('set', func_get_args());
    }

    public function __call($name, $args) {
    
        // what to do
        switch($name) {
        
            // add our namespace
            case 'get':
            case 'set':
            case 'add':
            case 'replace':
            case 'delete':
            case 'prepend':
            case 'append':
            
                // reset our key with
                // with the namespace
                $args[0] = "{$this->_ns}:{$args[0]}";
                
                // done            
                break;
            
            // get multi
            case 'setMulti':
            case 'getMulti':
            case 'getDelayed':
                
                // loop through each key
                foreach ($args[0] as $i => $key) {
                    $args[0][$i] = "{$this->_ns}:{$key}";
                }
            
                // done
                break;

            // by key
            case 'getByKey':
            case 'addByKey':
            case 'appendByKey':
            case 'deleteByKey':
            case 'prependByKey':
            case 'replaceByKey':
            case 'setByKey':
            
                // reset our key with
                // with the namespace
                $args[1] = "{$this->_ns}:{$args[1]}";
                
                // done            
                break;            
                
            // multi by key
            case 'setMultiByKey':
            case 'getMultiByKey':
            case 'getDelayedByKey':

                
                // loop through each key
                foreach ($args[1] as $i => $key) {
                    $args[1][$i] = "{$this->_ns}:{$key}";
                }
            
                // done
                break;

        };
    
        // try to execute
        if (method_exists($this->_handle, $name)) {
            return call_user_func_array(array($this->_handle, $name), $args);
        }
        else {
            return false;
        }        
    
    }


}