<?php

// autoload
spl_autoload_register(array('b', 'autoloader'));

// root
define("bRoot", dirname(__FILE__));


////////////////////////////////////////////////////////////
/// @brief bolt
////////////////////////////////////////////////////////////
class b {

    // plugins
    private static $plugin = array();
    private static $instance = array();

    // what defined our core
    private static $core = array(
        'config'    => "./bolt/config.php",
        'dao'       => "./bolt/dao.php",
        'mongo'     => "./bolt/source/mongo.php"
    );
    
    ////////////////////////////////////////////////////////////
    /// @brief initialize bolt
    ////////////////////////////////////////////////////////////    
    public static function init($args=array()) {
    
        // we need to include all of our core
        // plugins
        foreach (self::$core as $name => $file) {
            
            // see if it's relative
            if (substr($file,0,2) == './') { $file = bRoot."/{$file}"; }
            
            // if we have an autoload we should loop through it 
            // if ()
            
            // include it, only one
            include_once($file);
            
        }
    
    
    }

    ////////////////////////////////////////////////////////////
    /// @brief autoloader
    ////////////////////////////////////////////////////////////
    public static function autoloader($class) { 
    	
    	// we only want the last part of the class
    	$class = str_replace('\\', "/", $class);
      
        // see if the file exists in root
        if (file_exists(bRoot."/{$class}.php")) {
            return include_once(bRoot."/{$class}.php");
        }
    
        // config
        $autoload = b::config()->autoload;
        
        // if autoload
        if (is_array($autoload)) {
            foreach ($autoload as $root) {
                if (is_callable($root)) {
                    return call_user_func($root, $class);
                }
                else if (file_exists($root.$class.".php")) {
                    return include_once($root.$class.".php");
                }
            }        
        }
    
    }	



    ////////////////////////////////////////////////////////////
    /// @brief call one of our plugins
    ////////////////////////////////////////////////////////////
    public static function __callStatic($name, $args=array()) {
        
        // do we not have a plugin for this
        if (!array_key_exists($name, self::$plugin)) {
            return false;
        }
    
        // get it 
        $plug = self::$plugin[$name];
        
        // ask the class what it is
        if ($plug::$TYPE == 'factory') {
            return call_user_func(array($plug, "factory"), $args);
        }
        
        // singleton 
        else if ($plug::$TYPE == 'singleton') {
            
            // if we don't have an instance
            if (!array_key_exists($name, self::$instance)) {
                self::$instance[$name] = new $plug();
            }
        
            // shift off the arg as our method
            $method = array_shift($args);
            
            // instance
            $i = self::$instance[$name];
            
            // is it a string
            if (is_string($method)){ 
                return call_user_func_array(array($i, $method), $args);
            }
            else if (method_exists($i, "__default")) {            
                return call_user_func_array(array($i, "__default"), (is_array($method) ? array_merge(array($method), $args) : $args));
            }
            else {
                return $i;
            }
            
                        
        }
        
    }


    ////////////////////////////////////////////////////////////
    /// @brief forward to config-get
    ////////////////////////////////////////////////////////////    
    public static function _($name) {
        return b::config()->get($name);
    }

    ////////////////////////////////////////////////////////////
    /// @brief forward to config-set
    ////////////////////////////////////////////////////////////    
    public static function __($name, $value=false) {
        return b::config()->set($name, $value);
    }

    ////////////////////////////////////////////////////////////
    /// @brief plugin to bolt
    ////////////////////////////////////////////////////////////
    public static function plug($name, $class) {
        self::$plugin[$name] = $class;
    }

}
