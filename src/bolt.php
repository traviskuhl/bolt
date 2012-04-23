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

// set date
if (!defined("bTimeZone")) {
    define("bTimeZone", "UTC");
}

// set it
date_default_timezone_set(bTimeZone);

////////////////////////////////////////////////////////////
/// @brief static bolt wrapper instance
///
/// @class b
////////////////////////////////////////////////////////////
final class b {
    
    // public autoload
    public static $autoload = array();    

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
        'external'  => "./bolt/external.php",
        'account'   => "./bolt/account.php",
        'cache'     => "./bolt/cache.php",
        
        // source
        'source-mongo'      => "./bolt/source/mongo.php",        
        'source-webservice' => "./bolt/source/webservice.php",
        'source-s3'         => "./bolt/source/s3.php",
        'source-pdo'        => "./bolt/source/pdo.php",
        
        // renders
        'render-json'      => "./bolt/render/json.php",
        'render-xhr'       => "./bolt/render/xhr.php",
        'render-ajax'      => "./bolt/render/ajax.php",
        'render-html'      => "./bolt/render/html.php",
        'render-cli'       => "./bolt/render/cli.php",
        'render-xml'       => "./bolt/render/xml.php",
        
        // cache modules
        'cache-memcache'    => "./bolt/cache/memcache.php",
        
        // external
        'ext-s3'            => "./bolt/external/s3.php",
        'ext-drib'          => "./bolt/external/drib.php",
        'ext-fb'            => "./bolt/external/facebook.php"
        
    );    
    
    ////////////////////////////////////////////////////////////
    /// @brief return bolt instance
    ///
    /// @return bolt instance
    ////////////////////////////////////////////////////////////
    public static function bolt() {  
        if (!self::$instance) {
            self::$instance = new bolt();
        }      
        return self::$instance;
    }     

    ////////////////////////////////////////////////////////////
    /// @brief magic static to execute plugins. passthrough to
    ///         plugin::call method
    ///
    /// @see plugin::call
    ////////////////////////////////////////////////////////////
    public static function __callStatic($name, $args) {
        return call_user_func(array(self::bolt(), 'call'), $name, $args);
    }
    
    ////////////////////////////////////////////////////////////
    /// @brief static method to register a plugin. passthrough
    ///                 to plugin::plug method
    ///
    /// @see plugin::plug
    ////////////////////////////////////////////////////////////
    public static function plug() {
        return call_user_func_array(array(self::bolt(), 'plug'), func_get_args());
    }
    
    ////////////////////////////////////////////////////////////
    /// @brief return a list of core modules
    ///
    /// @return bolt instance
    ////////////////////////////////////////////////////////////
    public static function getCore() {  
        return array_keys(self::$core);
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
    
        // core always starts with the default
        $core = array_keys(self::$core);
        
        // nomods
        $skip = array();
            
        // loop through core
        if (array_key_exists('core', $args)) {
            foreach ($args['core'] as $mod) {
                if ($mod{0} == '-') {
                    $slip[] = substr($mod, 1);
                }
                else {
                    $core[] = $mod;
                }
            }
        }
    
        // we need to include all of our core
        // plugins
        foreach (self::$core as $name => $file) {
            
            // make sure they want us to load it
            if (!in_array($name, $core) OR in_array($name, $skip)) { continue; }
            
            // see if it's relative
            if (substr($file,0,2) == './') { $file = bRoot."/".ltrim($file,'./'); }        
            
            // include it, only one
            include_once($file);
            
        }
    
        // config
        if (isset($args['config'])) {
            b::config($args['config']);
        }
        
        // load
        if (isset($args['load'])) {
            self::load($args['load']);
        }
    
    }

    ////////////////////////////////////////////////////////////
    /// @brief load files
    ///
    /// @param $paths list of paths to load
    ////////////////////////////////////////////////////////////
    public static function load($paths) { 
        foreach($paths as $pattern) {
        
            // is it a file
            if (stripos($pattern, '.php') !== false AND stripos($pattern, '*') === false)  {
                $files = array($pattern);
            }
            else {
                $files = glob($pattern);            
            }
            
            // loop through each file
            foreach ($files as $file) {
            
                // template
                if (stripos($file, '.template.php') !== false) { continue; }
            
                // load it 
                include_once($file);
                
                // loaded
                self::$loaded[] = $file;
                
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
        $autoload = self::$autoload;
        
        // if autoload
        if (is_array($autoload)) {
            foreach ($autoload as $root) {
                $root = rtrim($root, '/').'/';            
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
    /// @brief get/set a configuration paramater. passhtrough to
    ///         config::get or config::set
    ///
    /// @param $name name of config param
    /// @return <mixed> config param or false if doesn't exist
    /// @see config::get
    ////////////////////////////////////////////////////////////
    public static function _($name, $value=null) {
        return ($value === null ? b::config()->get($name) : b::config()->set($name, $value));
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
	$path = b::config()->bPath;
	
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

    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) AND $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $_SERVER['SERVER_PORT'] = 443;
    }
    
    if (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
        $_SERVER['SERVER_PORT'] = $_SERVER['HTTP_X_FORWARDED_PORT'];
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

    // our path
    define("bPath",         "");

}
