<?php
////////////////////////////////////////////////////
// Copyright 2013 Travis Kuhl (travis@kuhl.co)
//
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
// files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
// modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom
// the Software is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
// WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
// COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF  CONTRACT, TORT
// OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
////////////////////////////////////////////////////

// autoload
spl_autoload_register(array('b', 'autoloader'));

// globals
$bGlobals = array(
    'bRoot' => dirname(__FILE__),
    'bEnv' => 'prod',
    'bTimeZone' => 'UTC',
    'bLogLevel' => 1,
    'bConfig' => "/etc/bolt/"
);

// check env for some bolt variables
foreach($bGlobals as $name => $default) {
    if (!defined($name) AND getenv($name)) {
        define($name, getenv($name));
    }
    else if (!defined($name)) {
        define($name, $default);
    }
}

// dev mode?
if ( bEnv === 'dev' ) {
    error_reporting(E_ALL^E_DEPRECATED^E_STRICT);
    ini_set("display_errors",1);
}

/// set our bzone
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
    const LogFatal = 3;

    // public autoload
    public static $autoload = array();

    // our plugin instance
    private static $_instance = false;
    private static $_loaded = array();
    private static $_settings = array(
        'project' => false
    );

    // what defined our core
    private static $_core = array(

        // general
        'config'    => "./bolt/config.php",
        'settings'  => "./bolt/settings.php",
        'dao'       => "./bolt/dao.php",
        'source'    => "./bolt/source.php",
        'cache'     => "./bolt/cache.php",
        'bucket'    => "./bolt/bucket.php",
        'event'     => "./bolt/event.php",

        // template renders
        'render'          => "./bolt/render.php",
        'render-mustache' => "./bolt/render/mustache.php",
        'render-markdown' => "./bolt/render/markdown.php",
        'render-handlebars' => "./bolt/render/handlebars.php",

        // source
        'source-mongo'      => "./bolt/source/mongo.php",
        'source-webservice' => "./bolt/source/webservice.php",
        'source-pdo'        => "./bolt/source/pdo.php",

        // cache modules
        'cache-memcache'    => "./bolt/cache/memcache.php",

    );

    public static $_modes = array(
        'browser' => array(
            // browser
            "./bolt/browser/controller.php",
            "./bolt/browser/view.php",
            "./bolt/browser/request.php",
            "./bolt/browser/response.php",
            "./bolt/browser/cookie.php",
            "./bolt/browser/helpers.php",

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
        ),
        'cli' => array(
            // cli
            "./bolt/cli.php",

            // cli plugins
            "./bolt/cli/arguments.php",
            "./bolt/cli/menu.php",
            "./bolt/cli/table.php"
        )
    );

    // mode & env
    public static $_mode = 'browser';

    ////////////////////////////////////////////////////////////
    /// @brief return env setting
    ///
    /// @return bolt instance
    ////////////////////////////////////////////////////////////
    public static function env() {
        return self::config()->get('global.env', 'prod');
    }

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

        // lig
        b::log("[b::init] called");

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

        // mode
        if (isset($args['mode']) AND array_key_exists($args['mode'], self::$_modes)) {
            self::load(self::$_modes[$args['mode']]);
            self::$_mode = $args['mode'];
        }

        // config
        if (defined('bConfig') AND bConfig !== false AND file_exists(bConfig."/config.ini")) {
          b::config()->import(bConfig."/config.ini", array('key' => 'global'));
        }

        // if we are init from the server
        // we need to look in our global
        if (p('src', false, $args) == 'server') {

            // name of
            if (!HOSTNAME) {
                b::log("Unable to get host from server", array(), b::LogFatal); return;
            }

            // normalzie host
            $host = strtolower(HOSTNAME);

            // start our assumeing we'll use the global project
            $project = b::config()->getValue('global.defaultProject');


            // figure out if we have a hostname that can
            // service this request
            foreach (b::config()->get('global')->asArray() as $key => $value) {

                if (is_array($value) AND array_key_exists('hostname', $value)) {
                    foreach ($value['hostname'] as $hn) { // not hackernews -> hostname
                        if (strtolower($hn) == $host) {
                            $project = $key; break;
                        }
                        else if (strtolower(implode('.', array_slice(explode('.', $hn), -2))) == $host) {
                            $project = $key; break;
                        }
                    }
                }
            }

            // no project
            if ($project === false) {
                b::log("Unable to match hostname (%s) to project.", array($host), b::LogFatal); return;
            }

            // project
            $project = b::config()->get('global')->get($project)->asArray();

            if (isset($project['load'])) {
                $args['load'] = $project['load']; unset($project['load']);
            }
            if (isset($project['settings'])) {
                $args['settings'] = $project['settings']; unset($project['settings']);
            }

            // everything else is config
            $args['config'] = $project;

        }

        // global load
        if (b::config()->get('global.load')->value) {
            b::load(b::config()->get('global.load')->asArray());
        }

        // config
        if (isset($args['config'])) {
            b::config($args['config']);
        }

        // settings or default project
        if (isset($args['settings'])) {
            self::$_settings['project'] = (is_a($args['settings'], '\bolt\settings') ? $args['settings'] : b::settings($args['settings']));
        }
        else {
            self::$_settings['project'] = b::bucket();
        }

        // load
        if (isset($args['load'])) {
            self::load($args['load']);
        }

        // ready
        b::fire('ready');

    }

    ////////////////////////////////////////////////////////////
    /// @brief deside how to run the framework
    ///
    /// @param $mode run mode
    /// @return void
    ////////////////////////////////////////////////////////////
    public static function run($mode=false) {
        if ($mode === false) {$mode = self::$_mode; }

        b::log("[b::run] %s", array($mode));

        // ready
        b::fire('run');

        // figure out how to run
        if ($mode == 'cli' OR ($mode === false AND php_sapi_name() == 'cli')) {

            // load our browser resources
            b::load( self::$_modes['cli'] );

            // dispatch the cli runner
            return b::cli()->run();

        }
        else {

            // load our browser resources
            b::load( self::$_modes['browser'] );

            // browser request
            return b::request()->run();

        }

    }


    ////////////////////////////////////////////////////////////
    /// @brief setting
    ///
    /// @param $name name of setting
    /// @param $default default value
    /// @return setting value
    ////////////////////////////////////////////////////////////
    public static function setting($name, $default=-1) {
        if ($default === -1) {$default = b::bucket(); }
        $type = 'project';
        if (stripos($name, '.') !== false) {
            $parts = explode(".", $name);
            $type = array_shift($parts);
            $name = implode(".", $parts);
        }
        $obj = (array_key_exists($type, self::$_settings) ? self::$_settings[$type] : self::$_settings['project']);
        return $obj->get($name, $default);
    }

    public static function setSettings($name, $file) {
        self::$_settings[$name] = b::settings($file);
    }
    public function getSettings($name) {
        return self::$_settings[$name];
    }


    ////////////////////////////////////////////////////////////
    /// @brief load files
    ///
    /// @param $paths list of paths to load
    ////////////////////////////////////////////////////////////
    public static function load($paths) {
        if (is_string($paths)) { $paths = array($paths); }

        foreach($paths as $pattern) {
            $files = array();

            // is it a file
            if (is_dir($pattern)) {
                self::_resursiveDirectorySerach($pattern, $files);
            }
            else if (stripos($pattern, '.php') !== false AND stripos($pattern, '*') === false)  {
                $files = array($pattern);
            }
            else {
                $files = glob($pattern);
            }

            // loop through each file
            foreach ($files as $oFile) {

                // see if it's relative
                if (substr($oFile,0,2) == './') {
                    $file = bRoot."/".ltrim($oFile,'./');
                }
                else {
                    $file = realpath($oFile);
                }

                // already loaded
                if (in_array($file, self::$_loaded)) {
                    b::log("[b::load] file '%s' already loaded", array($file)); continue;
                }

                // template
                if (stripos($file, '.template.php') !== false) { continue; }

                // file doesn't exist
                if (!file_exists($file)) {
                    b::log("[b::load] file '%s' does not exist", array($file)); continue;
                }

                b::log("[b::load] included file '%s'", array($file));

                // load it
                require_once($file);

                // loaded
                self::$_loaded[] = $file;

            }

        }
    }

        // nestedDirectory
        private static function _resursiveDirectorySerach($path, &$files) {
            $dirs = array();

            foreach (new DirectoryIterator($path) as $dir) {
                if ($dir->isFile() AND $dir->getExtension() == 'php') {
                    $files[] = $dir->getPathname();
                }
                else if ($dir->isDir() AND !$dir->isDot()) {
                    $dirs[] = $dir->getPathname();
                }
            }
            foreach ($dirs as $dir) {
                self::_resursiveDirectorySerach($dir, $files);
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
            foreach ($vars as $var) {
                if (!is_string($var)) {return;}
            }
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

    public static function on() {
        return call_user_func_array(array(b::bolt(), 'on'), func_get_args());
    }
    public static function fire() {
        return call_user_func_array(array(b::bolt(), 'fire'), func_get_args());
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
