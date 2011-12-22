<?php

// autoload
spl_autoload_register(array('b', 'autoloader'));

// root
if (!defined("bRoot")) {
    define("bRoot", dirname(__FILE__));
}

// devmode
if ( !defined("bDevMode") ) {
	define("bDevMode", ( getenv("bolt_framework__dev_mode") == 'true' ? true : false ));
}

	// dev mode?
	if ( defined('bDevMode') AND bDevMode === true ) {
	    error_reporting(E_ALL^E_DEPRECATED);
	    ini_set("display_errors",1);		
	}


////////////////////////////////////////////////////////////
/// @brief static bolt wrapper instance
///
/// @class b
////////////////////////////////////////////////////////////
final class b {

    // our plugin instance
    private static $instance = false;
    private static $loaded = array();
    
    // what defined our core
    private static $core = array(
    
        // general 
        'config'    => "./bolt/config.php",
        'dao'       => "./bolt/dao.php",
        'route'     => "./bolt/route.php",
        'render'    => "./bolt/render.php",
        'cookie'    => "./bolt/cookie.php",
        'session'   => "./bolt/session.php",
        'source'    => "./bolt/source.php",
        
        // source
        'source-mongo'     => "./bolt/source/mongo.php",        
        
        // renders
        'render-json'      => "./bolt/render/json.php",
        'render-xhr'       => "./bolt/render/xhr.php",
        'render-ajax'      => "./bolt/render/ajax.php",
        'render-html'      => "./bolt/render/html.php",
        
    );    

    ////////////////////////////////////////////////////////////
    /// @brief magic static to execute plugins. passthrough to
    ///         plugin::call method
    ///
    /// @see plugin::call
    ////////////////////////////////////////////////////////////
    public static function __callStatic($name, $args) {
        if (!self::$instance) {
            self::$instance = new bolt();
        }
        return call_user_func(array(self::$instance, 'call'), $name, $args);
    }
    
    ////////////////////////////////////////////////////////////
    /// @brief static method to register a plugin. passthrough
    ///                 to plugin::plug method
    ///
    /// @see plugin::plug
    ////////////////////////////////////////////////////////////
    public static function plug() {
        if (!self::$instance) {
            self::$instance = new bolt();
        }
        return call_user_func_array(array(self::$instance, 'plug'), func_get_args());
    }

    ////////////////////////////////////////////////////////////
    /// @brief initalize the bolt framework
    ///
    /// @param $args initalization arguments
    ///               - core: array of core plugins to load
    ///               - config: array of config params to set
    ///               - load: array of plugin folders to load. glob
    ///                         is run on each item
    /// @return void
    ////////////////////////////////////////////////////////////
    public static function init($args=array()) {
    
        // if no core
        if (!array_key_exists('core', $args)) {
            $args['core'] = array_keys(self::$core);
        }
    
        // we need to include all of our core
        // plugins
        foreach (self::$core as $name => $file) {
            
            // see if it's relative
            if (substr($file,0,2) == './') { $file = bRoot."/{$file}"; }
            
            // make sure they want us to load it
            if (!in_array($name, $args['core'])) { continue; }
            
            // include it, only one
            include_once($file);
            
        }
    
        // config
        if (isset($args['config'])) {
            b::config($args['config']);
        }
        
        // load
        if (isset($args['load'])) {
            foreach($args['load'] as $pattern) {
            
                // is it a file
                if (stripos($pattern, '.php') !== false)  {
                    $files = array($pattern);
                }
                else {
                    $files = glob($pattern);            
                }
                
                // loop through each file
                foreach ($files as $file) {
                
                    // load it 
                    include($file);
                    
                    // loaded
                    self::$loaded[] = $file;
                    
                }
                
            }
        }
    
    }
    
    ////////////////////////////////////////////////////////////
    /// @brief static return a list of loaded files
    ///
    /// @return array of loaded files
    ////////////////////////////////////////////////////////////
    public static function getLoaded() {
        return self::$loaded;
    }

    ////////////////////////////////////////////////////////////
    /// @brief autoload bolt components
    ///
    /// @param $class class name to load (namespace allowed)
    /// @return void
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
    /// @brief return bolt instance
    ///
    /// @return bolt instance
    ////////////////////////////////////////////////////////////
    public static function bolt() {    
        return self::$instance;
    }    

    ////////////////////////////////////////////////////////////
    /// @brief get a configuration paramater. passhtrough to
    ///         config::get
    ///
    /// @param $name name of config param
    /// @return <mixed> config param or false if doesn't exist
    /// @see config::get
    ////////////////////////////////////////////////////////////
    public static function _($name) {
        return b::config()->get($name);
    }

    ////////////////////////////////////////////////////////////
    /// @brief set a configuration paramater. passhtrough to
    ///         config::set
    ///
    /// @param $name name of config param
    /// @param $value value of config param to set
    /// @return <mixed> config param that was set
    /// @see config::set
    ////////////////////////////////////////////////////////////
    public static function __($name, $value=false) {
        return b::config()->set($name, $value);
    }

	// constants
	const SecondsInHour = 120;
	const SecondsInDay = 86400;
	const SecondsInWeek = 1209600;
	const SecondsInYear = 31536000;
	const DateLongFrm = "l, F jS, Y \n h:i:s A";
	const DateShortFrm = "F jS, Y \n h:i:s A";
	const DateTimeOnlyFrm = "l, F jS, Y";
	const TimeOnlyFrm = "h:i:s A";	
	const DefaultFilter = FILTER_SANITIZE_STRING;

}


////////////////////////////////////////////////////////////
/// @brief wrapper for single bolt instance
///
/// @class bolt
/// @extends bolt\plugin
////////////////////////////////////////////////////////////
final class bolt extends bolt\plugin {
    
    ////////////////////////////////////////////////////////////
    /// @brief construct a new bolt class. must also construct
    ///         parent class and pass fallback class list
    ///
    /// @see plugin::__construct
    ////////////////////////////////////////////////////////////
    public function __construct() {
        
        // init our plugin class
        parent::__construct(array(
            '\bolt\helpers'
        ));
                
    }

}

	
////////////////////////////////////////////////////////////
/// @brief global paramater check
///
/// @method	p
/// @param	$key	key name
/// @param	$default 	default value if key != exist [Default: false]
/// @param	$array		array to look in [Default: $_REQUEST]
/// @param  $filter    string to filter on the return
/// @return mixed paramater value
////////////////////////////////////////////////////////////
function  p($key, $default=false, $array=false, $filter=FILTER_SANITIZE_STRING) {

	// check if key is an array
	if ( is_array($key) ) {
	
		// alawys 
		$key = $key['key'];
		
		// check for other stuff
		$default = p('default',false,$key);
		$array = p('array',false,$key);
		$filter = p('filter',false,$key);
		
	}
	
	// no array
	if ( $array === false ) {
		$array = $_REQUEST;
	}
	
	// if there's a .
	if ( strpos($key, '.') !== false ) {
		
		// split on the .
		list($a, $k) = explode('.', $key);
				
		// reset array as p(a);
		$array = p($a, array(), false, $filter);
		
		// reset key
		$key = $k;
		
	}
	
	// not an array
	if ( !is_array($array) OR $key === false ){ return false; }

	
	// check 
	if ( !array_key_exists($key,$array) OR $array[$key] === "" OR $array[$key] === false OR $array[$key] === 'false' ) {
		return $default;
	}	

	
	// if final is an array,
	// weand filter we need to filter each el		
	if ( is_array($array[$key]) ) {
		
		// filter
		array_walk($array[$key],function($item,$key,$a){
			$item = p($key,$a[1],$a[0]);
		},array($filter,$array[$key]));

	}
	else {
	
		// array
		$array[$key] = filter_var($array[$key], $filter);
		
		// still bad
		if (!$array[$key]) {
			return $default;
		}
		
	}
	
	// reutnr
	return $array[$key];

}

////////////////////////////////////////////////////////////
/// @brief global raw paramater check
///
/// @method	p_raw
/// @param	$key	key name
/// @param	$default 	default value if key != exist [Default: false]
/// @param	$array		array to look in [Default: $_REQUEST]
/// @return mixed paratamer value
/// @see p 
////////////////////////////////////////////////////////////
function p_raw($key,$default=false,$array=false) {
	return p($key,$default,$array,FILTER_UNSAFE_RAW);
}

////////////////////////////////////////////////////////////
/// @brief global path paramater check
/// @method	p
/// @param	$pos	    index position of key
/// @param	$default 	default value if key != exist [Default: false]
/// @param	$array		array to look in [Default: $_REQUEST]
/// @param  $filter    string to filter on the return
/// @return mixed path paramter
////////////////////////////////////////////////////////////
function pp($pos,$default=false,$filter=false) {
		
	// path
	$path = bPath;
	
	if ( !$path ) { return $default; }
	
	// path 
	$path = explode('/',trim($path,'/'));
	
	// yes?
	if ( count($path)-1 < $pos OR ( count($path)-1 >= $pos AND $path[$pos] == "" ) ) {
		return $default;
	}

	// filter
	if ( $filter ) {
		$path[$pos] = preg_replace("/[^".$filter."]+/","",$path[$pos]);
	}
	
	// give back
	return $path[$pos];

}

// get the file name
$path = explode("/",$_SERVER['SCRIPT_FILENAME']);

// need to get base tree
$uri = explode('/',$_SERVER['SCRIPT_NAME']);  

// define 
if ( isset($_SERVER['HTTP_HOST']) ) {

    // forward
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    	$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
    }
    
    // forward
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    	$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
        $_SERVER['SERVER_PORT'] = 443;
    }
    
    define("HTTP_HOST",		 $_SERVER['HTTP_HOST']);
    define("HOST",      	 ($_SERVER['SERVER_PORT']==443?"https://":"http://").$_SERVER['HTTP_HOST']);
    define("HOST_NSSL",  	 "http://".$_SERVER['HTTP_HOST']);
    define("HOST_SSL",     	 "https://".$_SERVER['HTTP_HOST']);
    define("URI",      		 HOST.implode("/",array_slice($uri,0,-1))."/");
    define("URI_NSSL", 		 HOST_NSSL.implode("/",array_slice($uri,0,-1))."/");
    define("URI_SSL",  		 HOST_SSL.implode("/",array_slice($uri,0,-1))."/");
    define("COOKIE_DOMAIN",	 false);
    define("IP",			 $_SERVER['REMOTE_ADDR']);
    define("SELF",			 HOST.$_SERVER['REQUEST_URI']);
    define("PORT",			 $_SERVER['SERVER_PORT']);
    
    // our path
    define("bPath",         (getenv("REDIRECT_bPath")?getenv("REDIRECT_bPath"):getenv("bPath")));
    
}
else {

    define("HTTP_HOST",		 false);
    define("HOST",      	 false);
    define("HOST_NSSL",  	 false);
    define("HOST_SSL",     	 false);
    define("URI_NSSL", 		 false);
    define("URI_SSL",  		 false);
    define("COOKIE_DOMAIN",	 false);
    define("IP",			 false);
    define("SELF",			 false);
    define("PORT",			 false);

    if (!defined("URI")) {
        define("URI", false);	
    }

}
