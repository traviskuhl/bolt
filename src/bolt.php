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

if (!defined("bLogLevel")) {
    define("bLogLevel", 0);
}

// set it
date_default_timezone_set(bTimeZone);



////////////////////////////////////////////////////////////
/// @brief static bolt wrapper instance
///
/// @class b
////////////////////////////////////////////////////////////
final class b {

    // general constants
    const SecondsInHour = 120;
    const SecondsInDay = 86400;
    const SecondsInWeek = 1209600;
    const SecondsInYear = 31536000;
    const DateLongFrm = "l, F jS, Y \n h:i:s A";
    const DateShortFrm = "F jS, Y \n h:i:s A";
    const DateTimeOnlyFrm = "l, F jS, Y";
    const TimeOnlyFrm = "h:i:s A";
    const DefaultFilter = FILTER_SANITIZE_STRING;

    // log levels
    const LogNone = 0;
    const LogDebug = 1;
    const LogError = 2;

    // public autoload
    public static $autoload = array();

    // our plugin instance
    private static $_instance = false;
    private static $_loaded = array();

    // what defined our core
    private static $_core = array(

        // general
        'config'    => "./bolt/config.php",
        'dao'       => "./bolt/dao.php",
        'render'    => "./bolt/render.php",
        'source'    => "./bolt/source.php",
        'cache'     => "./bolt/cache.php",
        'bucket'    => "./bolt/bucket.php",

        // template renders
        'render-mustache' => "./bolt/render/mustache.php",
        'render-markdown' => "./bolt/render/markdown.php",

        // source
        'source-mongo'      => "./bolt/source/mongo.php",
        'source-webservice' => "./bolt/source/webservice.php",
        'source-pdo'        => "./bolt/source/pdo.php",

        // cache modules
        'cache-memcache'    => "./bolt/cache/memcache.php",

    );

    ////////////////////////////////////////////////////////////
    /// @brief return bolt instance
    ///
    /// @return bolt instance
    ////////////////////////////////////////////////////////////
    public static function bolt() {
        if (!self::$_instance) {
            self::$_instance = new bolt();
        }
        return self::$_instance;
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
        return array_keys(self::$_core);
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

        b::log("b::init called");

        // core always starts with the default
        $core = array_keys(self::$_core);
        $use = self::$_core;

        // nomods
        $skip = array();

        // loop through core
        if (array_key_exists('core', $args)) {
            foreach ($args['core'] as $mod) {
                if ($mod{0} == '-') {
                    unset($use[substr($mod, 1)]);
                }
            }
        }

        // we need to include all of our core
        // plugins
        b::load(array_values($use));

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
    /// @brief deside how to run the framework
    ///
    /// @param $mode run mode
    /// @return void
    ////////////////////////////////////////////////////////////
    public static function run($mode=false) {

        b::log("b::run %s", array($mode));

        // figure out how to run
        if ($mode == 'cli' OR ($mode === false AND php_sapi_name() == 'cli')) {

            // load our browser resources
            b::load(array(

                // cli
                "./bolt/cli.php",

                // cli plugins
                "./bolt/cli/arguments.php",
                "./bolt/cli/menu.php",
                "./bolt/cli/table.php"

            ));

            // dispatch the cli runner
            b::cli()->run();

        }
        else {

            // load our browser resources
            b::load(array(

                // browser
                "./bolt/browser/request.php",
                "./bolt/browser/response.php",
                "./bolt/browser/cookie.php",

                // routers
                "./bolt/browser/route.php",
                "./bolt/browser/route/regex.php",
                "./bolt/browser/route/token.php",

                // renders
                "./bolt/browser/response/json.php",
                "./bolt/browser/response/xhr.php",
                "./bolt/browser/response/ajax.php",
                "./bolt/browser/response/html.php",
                "./bolt/browser/response/xml.php",

            ));

            // browser request
            return b::request('execute');

        }

    }


    ////////////////////////////////////////////////////////////
    /// @brief load files
    ///
    /// @param $paths list of paths to load
    ////////////////////////////////////////////////////////////
    public static function load($paths) {
        if (is_string($paths)) { $paths = array($paths); }

        foreach($paths as $pattern) {

            // is it a file
            if (stripos($pattern, '.php') !== false AND stripos($pattern, '*') === false)  {
                $files = array($pattern);
            }
            else {
                $files = glob($pattern);
            }

            // loop through each file
            foreach ($files as $oFile) {

                // see if it's relative
                if (substr($oFile,0,2) == './') { $oFile = bRoot."/".ltrim($oFile,'./'); }

                // make sure it's the real path
                $file = realpath($oFile);

                // already loaded
                if (in_array($file, self::$_loaded)) {
                    b::log("b::load file '%s' already loaded", array($file)); continue;
                }

                // template
                if (stripos($file, '.template.php') !== false) { continue; }

                // file doesn't exist
                if (!file_exists($file)) {
                    b::log("b::load file '%s' does not exist", array($file)); continue;
                }

                b::log("b::load included file '%s'", array($file));

                // load it
                require($file);

                // loaded
                self::$_loaded[] = $file;

            }

        }
    }

    ////////////////////////////////////////////////////////////
    /// @brief static return a list of loaded files
    ///
    /// @return array of loaded files
    ////////////////////////////////////////////////////////////
    public static function getLoaded() {
        return self::$_loaded;
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
            self::$_loaded[] = realpath(bRoot."/{$class}.php");
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
                    self::$_loaded[] = realpath($root.$class.".php");
                    return include_once($root.$class.".php");
                }
            }
        }

    }

    ////////////////////////////////////////////////////////////
    /// @brief log a message somewhere
    ///
    /// @param $message the message to log
    /// @param $vars array of replacement vars
    /// @param $sev the log severity
    /// @reutrn bolt instance
    ////////////////////////////////////////////////////////////
    public static function log($message, $vars=array(), $sev=1) {
        if (bLogLevel === self::LogNone) { return self::bolt(); } // if no logging just stop now
        if (!is_array($vars)) {$sev = $vars;} // vars is really sev
        if ($sev >= bLogLevel) {
            array_unshift($vars, $message);
            error_log(call_user_func_array('sprintf', $vars));
        }
        return self::bolt();
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
