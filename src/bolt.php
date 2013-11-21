<?php
/**
 * bolt.php
 *
 * A PHP Framework
 *
 * @copyright  2010 - 2013
 * @author     Travis Kuhl (travis@kuhl.co)
 * @link       http://bolthq.com
 * @license    http://opensource.org/licenses/Apache-2.0 Apache 2.0
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

// start
define('bStart', microtime(true));

// autoload
spl_autoload_register(array('b', 'autoloader'));

// globals
$bGlobals = array(
    'bRoot' => dirname(__FILE__),
    'bEnv' => 'dev',
    'bTimeZone' => 'UTC',
    'bLogLevel' => 0,
    'bConfig' => false,
    'bPackage' => false
);

// check env for some bolt variables
foreach($bGlobals as $name => $default) {
    if (!defined($name) AND getenv($name) !== false) {
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

// set our bzone
date_default_timezone_set(bTimeZone);

/**
 * static bolt wrapper instance
 *
 * @class b
 */
final class b {

    const VERSION = "1.4.9";
    const BUILD = "";
    const BUILD_BRANCH = "";

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
    private static $_env = false;
    private static $_instance = false;
    private static $_loaded = array();
    private static $_mode = false;

    // package
    private static $_package = false;

    // what defined our core
    private static $_plugins = array(

        // general
        'bolt-core-config'   => "./bolt/config.php",
        'bolt-core-source'   => "./bolt/source.php",
        'bolt-core-cache'    => "./bolt/cache.php",
        'bolt-core-event'    => "./bolt/event.php",
        'bolt-core-package'  => "./bolt/package.php",
        'bolt-core-stream'   => "./bolt/stream.php",

        // cache
        'bolt-core-cache'   => "./bolt/cache.php",
        'bolt-core-cache-memcached' => "./bolt/cache/memcached.php",

        // bucket
        'bolt-core-bucket'          => "./bolt/bucket.php",
        'bolt-core-bycket-array'    => "./bolt/bucket/bArray.php",
        'bolt-core-bycket-string'    => "./bolt/bucket/bString.php",
        'bolt-core-bycket-object'    => "./bolt/bucket/bObject.php",

        // model
        'bolt-core-model'       => "./bolt/model.php",
        'bolt-core-model-base'  => "./bolt/model/base.php",
        'bolt-core-model-attr'  => "./bolt/model/attr.php",
        'bolt-core-model-attrs'  => "./bolt/model/attr/",

        // settings
        'bolt-core-settings' => "./bolt/settings.php",
        'bolt-core-settings-json' => "./bolt/settings/json.php",

        // template renders
        'bolt-core-render'          => "./bolt/render.php",
        'bolt-core-render-handlebars' => "./bolt/render/handlebars.php",
        'bolt-core-render-php' => "./bolt/render/php.php",
        'bolt-core-render-file' => "./bolt/render/file.php",

        // source
        'bolt-core-source-mongo'      => "./bolt/source/mongo.php",
        'bolt-core-source-curl'       => "./bolt/source/curl.php",
        'bolt-core-source-pdo'        => "./bolt/source/pdo.php",

        // browser
        'bolt-browser' => './bolt/browser.php',
        'bolt-browser-request' => "./bolt/browser/request.php",
        'bolt-browser-cookie' => "./bolt/browser/cookie.php",
        'bolt-browser-helpers' => "./bolt/browser/helpers.php",
        'bolt-browser-view' => "./bolt/browser/view.php",

        'bolt-browser-controller' => "./bolt/browser/controller.php",
        'bolt-browser-controller-callback' => "./bolt/browser/controller/callback.php",
        'bolt-browser-controller-request' => "./bolt/browser/controller/request.php",
        'bolt-browser-controller-module' => "./bolt/browser/controller/module.php",

        // routers
        'bolt-browser-route' => "./bolt/browser/route.php",
        'bolt-browser-route-parser' => "./bolt/browser/route/parser.php",
        'bolt-browser-route-token' => "./bolt/browser/route/token.php",

        // response
        'bolt-browser-response' => "./bolt/browser/response.php",
        'bolt-browser-response-json' => "./bolt/browser/response/json.php",
        'bolt-browser-response-xhr' => "./bolt/browser/response/xhr.php",
        'bolt-browser-response-ajax' => "./bolt/browser/response/ajax.php",
        'bolt-browser-response-html' => "./bolt/browser/response/html.php",
        'bolt-browser-response-xml' => "./bolt/browser/response/xml.php",
        'bolt-browser-response-javascript' => "./bolt/browser/response/javascript.php",
        'bolt-browser-response-json' => "./bolt/browser/response/json.php",

        // cli
        'bolt-cli' => "./bolt/cli.php",

    );

    // strandar modes
    public static $_modes = array(
        'core' => 'bolt-*',
        'browser' => 'bolt-browser-*',
        'cli' => 'bolt-cli-*',
    );

    /**
     * return bolt instance
     *
     * @return bolt instance
     */
    public static function bolt() {
        if (!self::$_instance) {
            self::$_instance = new bolt();
        }
        return self::$_instance;
    }

    /**
     * magic static to execute plugins. passthrough to
     *         plugin::call method
     *
     * @see plugin::call
     */
    public static function __callStatic($name, $args) {
        return call_user_func(array(self::bolt(), 'call'), $name, $args);
    }

    /**
     * static method to register a plugin. passthrough
     *                 to plugin::plug method
     *
     * @see plugin::plug
     */
    public static function plug() {
        return call_user_func_array(array(self::bolt(), 'plug'), func_get_args());
    }

    /**
     * return a list of core modules
     *
     * @return bolt instance
     */
    public static function getCore() {
        return array_keys(self::$_core);
    }

    /**
     * get/set env setting
     *
     * $env string env setting
     * @return bolt env
     */
    public static function env($env=false) {
        if ($env) {
            self::$_env = $env;
        }
        return (self::$_env ?: bEnv);
    }

    public static function mode($mode=false) {
        if ($mode) {
            self::$_mode = $mode;
        }
        return self::$_mode;
    }

    /**
     * depend on a plugin(s)
     *
     * @param $name name of plugin or wildcard match
     * @return self
     */
    public static function depend($name) {
        $load = array();
        if ($name == 'bolt-core' OR $name == 'core') { $name = 'bolt-core-*'; }

        if (strpos($name, '*') !== false) {
            // trim *- and try to load
            if (array_key_exists(trim($name,'*-'), self::$_plugins)) {
                $load[] = self::$_plugins[trim($name, '*-')];
            }
            $name = str_replace('*', '.*', preg_quote($name));
            foreach (self::$_plugins as $plugin => $file) {
                if (preg_match("#{$name}#i", $plugin)) {
                    $load[] = $file;
                }
            }
        }
        else if (array_key_exists($name, self::$_plugins)) {
            $load[] = self::$_plugins[$name];
        }

        // use
        b::load($load);

        // list of loaded modules
        return b::bolt();

    }

    public static function package($pkg=false) {
        if ($pkg) { self::$_package = $pkg; }
        return self::$_package;
    }

    /**
     * initalize the bolt framework
     *
     * @param $args initalization arguments
     *               - config: array of config params to set
     *               - load: array of plugin folders to load. glob
     *                         is run on each item
     * @return void
     */
    public static function init($args=array()) {

        // add our
        self::$autoload += explode(PATH_SEPARATOR, get_include_path());

        // mode
        if (isset($args['mode'])) {
            b::mode($args['mode']);
        }

        // we need to include all of our core
        // plugins
        b::depend('bolt-core-*');

        // config
        if (defined('bConfig') AND bConfig !== false AND file_exists(bConfig."/config.ini")) {
          b::config()->import(bConfig."/config.ini");
        }

        // pacakge?
        if (isset($args['package'])) {
            self::$_package = new \bolt\package($args['package']);
        }
        else if (defined("bPackage") AND bPackage !== false AND file_get_contents(bPackage)) {
            self::$_package = new \bolt\package(bPackage);
        }

        // autoload
        if (isset($args['autoload'])) {
            foreach ($args['autoload'] as $dir) {
                self::$autoload[] = $dir;
            }
        }

        // include
        if (b::config()->exists("autoload")) {
            foreach (b::config()->get("autoload") as $dir) {
                self::$autoload[] = $dir;
            }
        }

        // global load
        if (b::config()->exists('load')) {
            b::load(b::config()->get('load')->asArray());
        }

        // settings
        if (b::config()->exists('settings')) {
            foreach ( b::config()->value('settings') as $key => $value ) {
                b::settings()->set($key, $value);
            }
        }

        /// things from args

        // config
        if (isset($args['config'])) {
            b::config()->merge($args['config']);
        }

        // mode
        if (isset($args['mode'])) {
            b::load( self::$_modes[$args['mode']] );
        }

        // load
        if (isset($args['load'])) {
            self::load($args['load']);
        }

        // ready
        b::fire('ready');

    }

    /**
     * deside how to run the framework
     *
     * @param $mode run mode
     * @return void
     */
    public static function run($mode=false) {
        b::log("[b::run] %s", array($mode));

        // what mode
        b::mode($mode);

        // package
        $p = self::$_package;


        if ($p) {

            // config
            foreach($p->getConfig() as $key => $value ) {
                b::config()->set($key, $value);
            }

            // settings
            b::settings()->set("project", $p->getSettings());

            // set our root
            b::config()->set('root', $p->getRoot());


            // anything to load
            if ($p->getDirectories('load')) {
                b::load($p->getDirectories('load'));
            }

            // anything to load
            if ($p->getFiles('load')) {
                b::load($p->getFiles('load'));
            }

            // autoload
            if ($p->getDirectories('autoload')) {
                foreach ($p->getDirectories('autoload') as $dir) {
                    self::$autoload[] = $dir;
                }
            }



        }

        // ready
        b::fire('run');

        // figure out how to run
        if ($mode == 'cli' OR ($mode === false AND php_sapi_name() == 'cli')) {

            // clie
            b::depend('bolt-cli-*');

            // dispatch the cli runner
            return b::cli()->run();

        }
        else {

            // clie
            b::depend('bolt-browser-*');

            // run
            return b::browser()->run();

        }

    }

    /**
     * load files
     *
     * @param $paths list of paths to load
     */
    public static function load($paths) {
        if (is_string($paths)) { $paths = array($paths); }


        foreach($paths as $pattern) {
            $files = array();

            // is it a file
            if (substr($pattern,0,2) == './') {
                $pattern = b::path(bRoot, substr($pattern,2));
            }

            if (is_file($pattern)) {
                $files = array($pattern);
            }
            else if (stripos($pattern, '*') !== false) {
                $files = glob($pattern);
            }
            else if (is_dir($pattern)) {
                self::_resursiveDirectorySerach($pattern, $files);
            }
            else {
                foreach (explode(PATH_SEPARATOR , get_include_path()) as $dir) {
                    $file = b::path($dir, $pattern);
                    if (file_exists($file)) {
                        $files = array($file);
                    }
                }
            }

            // loop through each file
            foreach ($files as $oFile) {


                // tests
                if (basename($oFile) == 'tests') {continue;}

                // see if it's relative
                if (substr($oFile,0,2) == './') {
                    $file = bRoot."/".ltrim($oFile,'./');
                }
                else if (realpath($oFile)) {
                    $file = realpath($oFile);
                }
                else {
                    $file = $oFile;
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
            if (!is_dir($path)) {return;}

            foreach (new DirectoryIterator($path) as $dir) {
                if ($dir->isFile() AND $dir->getExtension() == 'php') {
                    $files[] = $dir->getPathname();
                }
                else if ($dir->isDir() AND !$dir->isDot() AND $dir->getFileName() != 'tests' ) {
                    $dirs[] = $dir->getPathname();
                }
            }

            foreach ($dirs as $dir) {
                self::_resursiveDirectorySerach($dir, $files);
            }
        }

    /**
     * static return a list of loaded files
     *
     * @return array of loaded files
     */
    public static function getLoaded() {
        return self::$_loaded;
    }

    /**
     * autoload bolt components
     *
     * @param $class class name to load (namespace allowed)
     * @return void
     */
    public static function autoloader($class) {

        // valid
        $isValidFileName = function($str) {
            return !preg_match('#[^a-zA-Z0-9\.\/\-\_]#', $str);
        };

    	// we only want the last part of the class
    	$class = str_replace('\\', "/", $class);

        if (!$isValidFileName($class)) {return;}

        // see if the file exists in root
        if (file_exists(bRoot."/{$class}.php")) {
            self::$_loaded[] = realpath(bRoot."/{$class}.php");
            return include_once(bRoot."/{$class}.php");
        }

        // if autoload
        if (is_array(self::$autoload)) {
            foreach (self::$autoload as $root) {
                $root = rtrim($root, '/').'/';
                if ($isValidFileName($root.$class.".php") AND file_exists($root.$class.".php")) {
                    self::$_loaded[] = realpath($root.$class.".php");
                    return include_once($root.$class.".php");
                }
                else if (strpos($class, '_') !== false) {
                    $cl = str_replace("_", "/", $class). ".php";
                    if ($isValidFileName($root.$cl) AND file_exists($root.$cl)) {
                        return include_once($root.$cl);
                    }
                }
            }
        }

    }

    /**
     * log a message somewhere
     *
     * @param $message the message to log
     * @param $vars array of replacement vars
     * @param $sev the log severity
     * @reutrn bolt instance
     */
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

    /**
     * get/set a configuration paramater. passhtrough to
     *         config::get or config::set
     *
     * @param $name name of config param
     * @return <mixed> config param or false if doesn't exist
     * @see config::get
     */
    public static function _($name, $value=null) {
        return ($value === null ? b::config()->get($name) : b::config()->set($name, $value));
    }

    public static function on() {
        return call_user_func_array(array(b::bolt(), 'on'), func_get_args());
    }
    public static function fire() {
        return call_user_func_array(array(b::bolt(), 'fire'), func_get_args());
    }

    /**
     * global paramater check
     *
     * @method  p
     * @param   $key    key name
     * @param   $default    default value if key != exist [Default: false]
     * @param   $array      array to look in [Default: $_REQUEST]
     * @param  $filter    string to filter on the return
     * @return mixed paramater value
     */
    public static function  param($key, $default=false, $array=false, $filter=FILTER_SANITIZE_STRING) {

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
            $array = b::param($a, array(), false, $filter);

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
                $item = b::param($key,$a[1],$a[0]);
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

    /**
     * global raw paramater check
     *
     * @method  p_raw
     * @param   $key    key name
     * @param   $default    default value if key != exist [Default: false]
     * @param   $array      array to look in [Default: $_REQUEST]
     * @return mixed paratamer value
     * @see p
     */
    public function param_raw($key,$default=false,$array=false) {
        return p($key,$default,$array,FILTER_UNSAFE_RAW);
    }

    public static function a($array) {
        return new \bolt\bucket\bArray($array);
    }
    public static function s($str) {
        return new \bolt\bucket\bString($str);
    }

}


/**
 * wrapper for single bolt instance
 *
 * @class bolt
 * @extends bolt\plugin
 */
final class bolt extends bolt\plugin {

    /**
     * construct a new bolt class. must also construct
     *         parent class and pass fallback class list
     *
     * @see plugin::__construct
     */
    public function __construct() {

        // init our plugin class
        parent::__construct(array(
            '\bolt\helpers'
        ));

    }

}

// shortcut
function b() {
    return call_user_func_array(b::bolt(), func_get_args());
}

