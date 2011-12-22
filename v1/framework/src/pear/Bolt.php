<?php // (c) 2010 - bolthq

	// auto load
    // tell the autoloader where the locations to look
    // for our files are
    $GLOBALS['_auto_loader'] = array(
        array( '.php', bFramework),
        array( '.php', bFramework.'Ext/'),
    	array( '.dao.php', "/home/bolt/share/pear/bolt/"), 
    );	
    
    // unless defined
    if ( !defined("bConfig") ) {
		define("bConfig",		"/home/bolt/config/");
	}
	if ( !defined("b404") ) {
		define("b404",			"/home/bolt/share/htdocs/404.php");
	}
	if ( !defined("bDevMode") ) {
		define("bDevMode", ( getenv("bolt_framework__dev_mode") == 'true' ? true : false ));
	}
	if ( !defined("bProject") ) {
		define("bProject", getenv("bProject"));    
	}
	
	///////////////////////////////////
	/// @brief autoload class class
	///
	/// @param $class class name
	///////////////////////////////////	
	class BoltLoader {
		
		public static function autoloader($class) { 
			
			// we only want the last part of the class
			$class = str_replace('\\', "/", $class);
		
			// check for autoload in global
			if ( !isset($GLOBALS['_auto_loader']) ) {
				return;
			}
			
			// try to find it
			foreach ( $GLOBALS['_auto_loader'] as $path ) {
					
				// if we should convert _ to /
				if ( isset($path[2]) AND $path[2] == true ) {
					$class = str_replace("_","/",$class);
				}
			
				// file name
				$file = b::formatDirName($path[1]).$class.$path[0];		
			
				// does it exist
				if ( file_exists($file) ) {
					require_once($file); return;
				} 
				else if ( file_exists( strtolower($file) ) ) {
					require_once(strtolower($file)); return;
				}
				
			}
		
		}	
		
	}
	
	// autoload
	spl_autoload_register(array('BoltLoader', 'autoloader'));
	
	// dev mode?
	if ( defined('bDevMode') AND bDevMode === true ) {
	
		// error reporting
	    error_reporting(E_ALL^E_DEPRECATED);
	    
	    // display errors
	    ini_set("display_errors",1);		
	    
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
	    define("PORT",			$_SERVER['SERVER_PORT']);
	    
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
	
	// bolt modules
	define("BOLT_MODULES", "/home/bolt/share/pear/bolt/modules");
	
	// date
	date_default_timezone_set("UTC");	
		
	// modules we always need that are not named
	require(bFramework."Database.php");
	require(bFramework."MongoDatabase.php");
	
	// we need their project config
	Config::load( bConfig . bProject . ".ini");
	
		// more config
		$loadConfig = array();
		
		// any other config files
		if ( ($_load = getenv("bLoadConfig")) !== false ) {
			$loadConfig += explode(",", $_load);
		}
		if ( defined('bLoadConfig') ) {
			$loadConfig += explode(",", bLoadConfig);
		}				
		if (count($loadConfig) > 0) {
			foreach ($loadConfig as $file ) {
				Config::load( bConfig . trim($file) . ".ini");				
			}		
		}

	// add dao to autoload
	if ( is_array(Config::get('autoload/file')) ) {
		$GLOBALS['_auto_loader'] = array_merge(Config::get('autoload/file'), $GLOBALS['_auto_loader']);
	}	

	// bolt js
	b::__('bolt-global', "/assets/bolt/js/global.js");


	// now that we've loaded all config files
	// we need to check on assets
	if ( !bDevMode ) {
		
		// static to use
		$static = ((bool)b::_("embeds/use_ssl") ? 'static-ssl' : 'static');
	
		// cid
		$cid ="bolt.assets.manifest";
		
		// manifest
		if ( ($manifest = apc_fetch($cid) ) == false AND file_exists("/home/bolt/var/bolt/warhol.manifest") ) { 
		
			// get it 
			$manifest = json_decode(file_get_contents("/home/bolt/var/bolt/warhol.manifest"),true);
			
			// save it 
			apc_store($cid, $manifest);
			
		}
		
		// globalize bolt manifest
		b::__("bolt/manifest", $manifest);
		
		// embeds
		$embeds = b::_('embeds');
		
		// loop through each and see if they're in css or js
		if (is_array($embeds)) {
			foreach ( $embeds['js'] as $k => $js ) {
				$name = key($js);
				switch($name) {
					case 'bolt': $embeds['js'][$k][$name] = $manifest['bolt.js'][$static]; break;
					case 'bolt-class-panel': $embeds['js'][$k][$name] = $manifest['panel.js'][$static]; break;
				}
			}
		}
	
		// reset embeds
		b::__('embeds', $embeds);
		
		// cdn it
		b::__('bolt-global', $manifest['global.js'][$static]);
	
	}

	
	////////////////////////////////
	///  @breif config
	////////////////////////////////
	abstract class Bolt {
	
		// project
		public static $project = bProject;
	
		////////////////////////////////
		///  @breif start
		////////////////////////////////	
		public static function start() { }	
	
	
		////////////////////////////////
		///  @breif prePage
		////////////////////////////////	
		public static function prePage() {}
		
		
		////////////////////////////////
		///  @breif preRoute
		////////////////////////////////		
		public static function preRoute() {}
	
	
		////////////////////////////////
		///  @breif preRoute
		////////////////////////////////			
		public static function getPage($path=false) {
		  
	        // default page
    	    $page = "404";
    	    
    	    	// default page
    	    	if ( Config::get('site/defaultPage') ) {
    	    		$page = Config::get('site/defaultPage');
    	    	}    	    	
       
			// path
        	$path = ( $path == false ? (getenv("REDIRECT_bPath")?getenv("REDIRECT_bPath"):getenv("bPath")) : $path );
                
    	    	// no path
    	    	if (!$path) {
    	    		return $page;
    	    	}                
                
			// save the uri
			b::__('_bUri', $path);
                
	        // check for assets
    	    if ( trim($path,'/') == 'combo' ) {
        	    Controller::printAssets( p('f'),p('type','css') );
        	}
                
			// pages
			$pages = Config::get('pages');
			
			// ajax 
			if ( !isset($pages['ajax']) ) {
				$pages['ajax'] = array(
					"uri" => "ajax/(modules|pages)/([a-zA-Z0-9]+)/?(.*)?/?",
					'_bType' => 1,					
					'_bModule' => 2,
					'_bPath' => 3,
					'_bContext' => 'ajax'
				);
			}
			
			// xhr
			if ( !isset($pages['xhr']) ) {
				$pages['xhr'] = array(
					"uri" => "xhr/(modules|pages)/([a-zA-Z0-9]+)/?(.*)?/?",
					'_bType' => 1,					
					'_bModule' => 2,
					'_bPath' => 3,
					'_bContext' => 'xhr'					
				);
			}								    
			                  
			// go through and parse the path, look for matches (defined above)
			foreach ($pages as $pg => $args) {
			
				// look for matches based on rewrite rules
				if (preg_match('#'.$args['uri'].'#',$path,$matches)) {
					
					// this is our page
					$page = $pg;
					
						// check if _bHost
						if(isset($args['_bHost']) AND !in_array(HTTP_HOST, $args['_bHost'])) {
							continue;
						}		
						
						// override with page
						if ( isset($args['_bPage']) ) {
							$page = ( (is_int($args['_bPage']) AND isset($matches[$args['_bPage']])) ? $matches[$args['_bPage']] : $args['_bPage']); unset($args['_bPage']);
						}
						
						// bcontext
						if ( isset($args['_bContext']) ) {
							b::__('_bContext', $args['_bContext']); unset($args['_bContext']);
						}
			                      
					// set other arguments in the GET
					if ( is_array($args) ) {
						foreach ($args as $a => $v) {
				
							// uri
							if ( $a == 'uri' ) { continue; }
							
							// is int
							if (is_int($v) AND isset($matches[$v])) {                                                  
								$_REQUEST[$a] = $matches[$v];                                          
							}
							else if ( !is_numeric($v) ) {
								$_REQUEST[$a] = $v;                                            
							}
						
						}
					}
			
					//no need to continue the matching
					break;
			
				}
			
            }
            
            // check for context
            if ( p('_bContext') == 'xhr' AND $page != 'xhr' ) {
            	
            	// override _bPage
            	$_REQUEST['_bModule'] = $page;
				$_REQUEST['_bType'] = 'pages';	
            
            }
            
            // path
			if ( isset($_REQUEST['_bPath']) AND $_REQUEST['_bPath'] ) { 
			
				// set path
				b::__('_bPath', trim(p('_bPath'),'/'));
				
				// unset
				unset($_REQUEST['_bPath']);
				
			}
			else if ( !isset($_REQUEST['_bPath']) ) {
				// set path
				b::__('_bPath', trim(b::_('_bUri'),'/'));						
			}
			
			// set page
			b::__("_bPage", $page);		
                                
    	    // give back
	        return $page;		
		
		}
	
	}
	

	////////////////////////////////
	///  @breif config
	////////////////////////////////
	class Config {	
	
		/// config holder array
		private static $config = array();		
	
	
		//////////////////////////////////////////
		///  @breif load a settings file		
		///
		///  @param $file full path to settings file
		///					file must exists
		//////////////////////////////////////////
		public static function load($file) {
		
			// not there
			if ( !file_exists($file) ) { return false; }
			
			// cid
			$cid = "bolt:ini:" . md5($file);
			
			// if we're no in devmode we can check the 
			// cache for the ini file
			if ( bDevMode OR ($ini = apc_fetch($cid)) == false ) {
				$ini = parse_ini_file($file, true, INI_SCANNER_RAW);
				apc_store($cid, serialize($ini));				
			}
			
			// unserialize it
			if (!is_array($ini)) {
				$ini = unserialize($ini);
			}
			
			// still not an ini means we stop
			if ( !is_array($ini) ) { return false;}
			
			// format
			$format = function($v, $ini, $sec) {						
				
				// matched
				$match = array();
				$i = 0;
				
				// check for any %
				while ( preg_match_all("/\%([a-zA-Z0-9\.\_]+)\%/", $v, $match, PREG_SET_ORDER) AND $i++ < 5 ) {
					
					// loop through the matches and try to 
					// replace them
					foreach ( $match as $m ) {
						
						// get the sec and key
						list($sec, $key) = explode(".", $m[1]);
						
						// replace
						if ( isset($ini[$sec][$key]) ) {
							$v = str_replace($m[0], $ini[$sec][$key], $v);
						}
						
					}
				
				}
				
				// json
				if ( substr($v,0,1) == '{' AND $sec != 'urls' ) {
					$v = json_decode($v, true);
					
					// nope
					if (bDevMode AND !$v) {
						exit("could not decode json INI settings for {$sec}");
					}
					
				}
				
				if (is_string($v)) {
					$v = trim($v,"\"'\t");
				}
				
				// ack
				return $v;
							
			};
					
						
			// loop through each section and set
			foreach ( $ini as $sec => $set ) {
				
				// need to sanatize 
				foreach ( $set as $k => $v ) {
				
					// k is uri
					if ( $sec == 'urls' ) {
						$v = str_replace("--", "=", $v);
					}
				
					// is v and 
					if ( is_array($v) ) {
						foreach ( $v as $i => $_v ) {
							$ini[$sec][$k][$i] = $format($_v, $ini, $sec);
						}
					}
					else {
						$ini[$sec][$k] = $format($v, $ini, $sec);
					}
				

				}
								
				// try to unset the sec
				if ( $sec{0} == '-' ) {
					$nsec = substr($sec,1);
					self::remove($nsec);
					self::set($nsec, $ini[$sec]);
				}
				else {
					self::set($sec, $ini[$sec]);
				}
								
			}
			
			
		}


		public static function getData() { return self::$config; }
		
		////////////////////////////////
		/// @breif get a predefined config
		////////////////////////////////		
		public static function get($var,$isPath=false) {
			
			// config
			$config = self::$config;
			
				// check for a sub
				if ( strpos($var,'/') !== false AND is_array($config) ) {
					list($ary,$var) = explode('/',$var);

					if ( isset($config[$ary]) ) {
						$config = $config[$ary];
					}
				}			

			// what evn
			$var_pf = $var . (bDevMode?'_dev':'_prod');
			
			// val
			$val = false;
			
			// var
			if ( isset($config[$var_pf]) ) {
				$val = $config[$var_pf];
			}
			else if ( isset($config[$var]) ) {
				$val = $config[$var];
			}
			
			return ( $isPath ? "/".trim($val,"/")."/" : $val );
			
		}
	
		////////////////////////////////
		/// @breif set a config val
		////////////////////////////////
		public static function set($var, $val) {	
						
			// $a
			$a = false;						
						
			// if it already exists and is an array
			// we need to merge 
			if ( isset(self::$config[$var]) AND is_array($val) ) {
				 $a = b::mergeArray(self::$config[$var], $val);									
			}
			else {			
				$a = $val;		
			}
		
			// check for a sub
			if ( strpos($var,'/') !== false ) {
				list($ary,$var) = explode('/',$var);
				self::$config[$ary][$var] = $a;			
			}		
			else {
				self::$config[$var] = $a;
			}
			
		}
		
		// replace
		public static function replace($key, $val) {
			self::$config[$key] = $val;
		}
	
		////////////////////////////////
		/// @breif unset a config val
		////////////////////////////////
		public static function remove($var) {	
		
			// unset a config var
			unset(self::$config[$var]);
			
		}	
	
	
		////////////////////////////////
		/// @breif get a url
		////////////////////////////////		
		public static function url($key, $data=false, $params=false, $uri=URI) {
			
			// key = 'slef'
			if ( $key == 'self' ) {
				return SELF;
			}
			
			// define our urls
			$pages = self::$config['urls'];
			
			// get a url
			if ( array_key_exists($key,$pages) ) {
				$url = $pages[$key]; 
			}
			else {
				$url = $key;
			}

			
			// repace toeksn
			if ( is_array($data) ) {
							
				foreach ( $data as $k => $v ) {
					if ( !is_array($k) AND !is_array($v) ) {
				    			
				        // orig 
				        $orig = $v;		
				    				        
						// check for * in key
						if ( substr($k,0,1) != '*' ) {
							$v = strtolower(preg_replace(
								array("/[^a-zA-Z0-9\-\/]+/","/-+/"),
								"-",
								html_entity_decode($v,ENT_QUOTES,'utf-8')
							));						
						}
						else {
							$k = substr($k,1);
						}
						
						// url
						$url = str_replace('{*'.$k.'}',$orig,$url);
						$url = str_replace('{'.$k.'}',trim($v,'-'),$url);
						
					}
					else if ( is_array($v) ) {
                        
                        foreach ( $v as $kk => $vv ) {
                            if ( is_string($vv)) {
                                $url = str_replace('{*'.$k.'['.$kk.']}',$vv,$url);                            
                                $url = str_replace('{'.$k.'['.$kk.']}',$vv,$url);
                            }
                        }
					
					}
				}
			}
			
			// clean up
			$url = preg_replace("/\{\*?[a-z\[\]]+\}\/?/","",$url);
			
			// params
			if ( is_array($params) ) {
				$p = array();
				foreach ( $params as $k => $v ) {
					$p[] = "{$k}=".urlencode($v);
				}
				$url .= (strpos($url,'?')==false?'?':'&').implode('&',$p);
			}
			else {
				$url = trim($url,'/');
			}
			
			// give back
			if (stripos($url,"http://") === 0) { 
				return $url;
			} else { 
				return $uri . $url;
			}
		
		}
		
		static function addUrlParams($url,$params) {
		
			// parse the url
			$u = parse_url($url);
		
			// loop and add to params
			if ( isset($u['query']) ) {
				foreach ( explode('&',$u['query']) as $i ) {
					if ( $i ) {
						list($k,$v) = explode('=',$i);
						if ( !array_key_exists($k,$params) ) {
							$params[$k] = $v;
						}
					}
				}
			}
			
			// reconstruct
			$url = $u['scheme']."://".$u['host'].(isset($u['port'])?":{$u['port']}":"").$u['path'];
		
			$p = array();
			foreach ( $params as $k => $v ) {
				$p[] = "{$k}=".urlencode($v);
			}
			$url .= (strpos($url,'?')==false?'?':'&').implode('&',$p);		
			
			if ( isset($u['fragment']) ) {
				$url .= $u['fragment'];
			}
			
			return $url;
		
		}
	
	
	}

	// bolt
	class b {

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
		
	
		// private vars
		private static $instance = false;
		public $attached = array();
		
		public $db, $session, $user, $logged = false;
		
		private function __construct() {
			
			// lets create some shortcuts
			$this->db = Database::singleton();
			$this->session = Session::singleton();
			$this->user = Session::singleton()->getUser();
			$this->logged = Session::singleton()->getLogged();
		
		}
		
		public static function cache() {
		    return Cache::singleton();
		}
		
		public static function controller() {
			$args = func_get_args();
			$m = array_shift($args);
			return call_user_func_array("\Controller::{$m}", $args);
		}
		
		public static function attach($name, $func) {
		
			// get the instance
			$i = self::singleton();
			
			// attach our callback
			$i->attached[$name] = $func;
			
		}
		
		public static function singleton() {
		
			// if none, create one
			if ( !self::$instance ) {
				$class = __CLASS__;
				self::$instance = new $class();
			}
		
			// give back
			return self::$instance;
			
			}
		
		// event
		public static function events() {
			return Events::singleton();
		}
		
		// call
		public static function c() {	
		
			$args = func_get_args();
			$name = array_shift( $args );
	
			// single
			$s = b::singleton();
													
			// is there
			if ( property_exists($s, $name) ) {
			
				// method
				$method = array_shift($args);			
			
				// call func
				if ( $method ) {
					return call_user_func_array(array($s->{$name}, $method), $args);
				}
				
			}
			else if ( array_key_exists($name, $s->attached) ) {
				try { 
					return call_user_func_array($s->attached[$name], $args);
				}
				catch (Exception $e){ throw $e; }
			}
		
			// nope
			return false;
		
		}
		
		public static function url() {
			return call_user_func_array(array("Config","url"), func_get_args());
		}
		
		public static function config() {
			
			// need at least one
			if ( func_num_args() == 0 ) { return false; }
			
			// args
			$args = array_slice( func_get_args(), 1 );
			
			// call
			return call_user_func_array(array("Config", func_get_arg(0)), $args);
			
		}
	
		// get 
		public function _($key) {
			return self::config('get',$key);
		}

		public function __($key, $val, $reg=false) {
		
			// register with controller?
			if ( $reg === true ) {
				Controller::registerGlobal($key, $val);
			}
		
			// set in config
			return self::config('set', $key, $val);
			
		}
		
		public static function mergeArray($a1, $a2) {
			foreach ( $a2 as $k => $v ) {
				if ( array_key_exists($k, $a1) AND is_array($v) ) {
					$a1[$k] = self::mergeArray($a1[$k], $a2[$k]);
				}
				else {
					$a1[$k] = $v;
				}
			}
			return $a1;
		}		
	
    public static function randString($len=30) {
        // chars
        $chars = array(
                'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',
                'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','V','T','V','U','V','W','X','Y','Z',
                '1','2','3','4','5','6','7','8','9','0'
        );
       
        // suffle
        shuffle($chars);
       
        // string
        $str = '';
       
        // do it
        for ( $i = 0; $i < $len; $i++ ) {
                $str .= $chars[array_rand($chars)];
        }
       
        return $str;   

	}		
	
	
		public static function setCookie($name, $value, $expires=false) {
		
			// domain
			$domain = self::_('site/cookieDomain');
		
			// get name from config
			if ( substr($name, 0, 1) == '$' ) {
				$name = self::_(substr($name, 1));
			}
		
			// is value a string
			if ( is_array($value) ) {
			
				// see if this cookie already exists
				if ( p($name, false, $_COOKIE) ) {
					
					// merge the values from the current cookie
					$value = self::mergeArray( self::getCookie($name), $value );
					
				}
				
				// encode it
				$e = base64_encode(json_encode($value));
			
				// return the padded value
				$value = ":".self::md5($e).$e;
				
			}
			
			// expires
			if ( $expires AND $expires < self::utctime() ) {
				$expires = self::utctime() + $expires;
			}
		
			// set it 
			setcookie($name, $value, $expires, '/', $domain);
		
		}
		
		public static function deleteCookie($name) {

			// domain
			$domain = self::_('site/cookieDomain');

			// get name from config
			if ( substr($name, 0, 1) == '$' ) {
				$name = self::_(substr($name, 1));
			}

			// set it 
			setcookie($name, false, time()+1, '/', $domain);
		
		}
		
		public static function getCookie($name) {

		
			// get name from config
			if ( substr($name, 0, 1) == '$' ) {
				$name = self::_(substr($name, 1));
			}
		
			// try to get it 
			$cookie = urldecode(p($name, false, $_COOKIE));
		
				// if we don't have it, stop
				if ( !$cookie ) { return false; }
				
			// see if the first val is a :
			if ( $cookie{0} == ':' ) {
								
				// make sure we're goof
				$cookie = self::getDecodedCookie($cookie);
				
			}
		
			// return it 
			return $cookie;
		
		}		
		
		public static function getDecodedCookie($cookie) {

			// split it 
			$sig = substr($cookie, 1, 32);
			
			// now the value
			$e = substr($cookie, 33);
			
			// make sure we're goof
			return ( self::md5($e) == $sig ? json_decode(base64_decode($e), true) : false );
			
		}
		
		public function md5($str) {
			return md5('A#DK@()jdm2d89uddp2[;d3.2p'.$str.'$Kd90aa23d2i9k30dpdkjuf');
		}
		
		public function location() {
			$a = func_get_args();
			$url = $a[0];			
			if ( $url{0} == '$' ) {
				$a[0] = substr($url,1);
				$url = call_user_func_array("b::url",$a);
			}
			else if ( isset($a[1]) ) {
				if ( stripos($url,'http') === false )  {
					$url = URI.trim($url,'/');
				}						
				$url = Config::addUrlParams($url, $a[1]);
			}

			exit(header("Location:$url"));
		}
	
		public static function makeSlug($str) {
			
			// remove any ' in the str
			$str = str_replace("'",'', html_entity_decode($str, ENT_QUOTES, 'utf-8') );
		
			// search
			$search = array(
				"/([^a-zA-Z0-9]+)/",
				"/([-]{2,})/"
			);
		
			// now the bug stuff
			return strtolower(trim(preg_replace($search, '-', $str),'-'));
		
		}
		
		public static function uuid($parts=5, $prefix=false) { return self::getUuid($parts, $prefix); }
			
		public static function getUuid($parts=5, $prefix=false) {
		
			// uuid
			$id = `uuid`;
		
			// uuid
			$uuid = array_slice(explode('-',trim($id)),0,$parts);
				
				// prefix
				if ( $prefix ) { $uuid = array_merge(array($prefix), $uuid); }
	
			return strtolower(implode('-',$uuid));
		}		
	
		public static function utctime() {
		
			// datetime
			$dt = new DateTime('now',new DateTimeZone('UTC'));		
			
			// return utctime
			return $dt->getTimestamp();
		
		}
		
		public static function plural($str, $count, $multi=false) {
			if ( is_array($count) ) { $count = count($count); }
			
			if ( $multi !== false ) {
				return ( $count!=1 ? $multi : $str );
			}
			
			if ( substr($str,-1) == 'y' AND $count > 1 ) {
				return substr($str,0,-1)."ies";
			}
			return $str . ($count!=1?'s':'');
		}

		public static function possesive($str) {
			if ( strtolower($str) == 'you' ) { return $str{0}.'our'; }
			return $str . (substr($str,-1)=='s'?"'":"'s");
		}
		
		public static function niceDate($ts) {
			$diff = b::utctime() - $ts;
			if ($diff < b::SecondsInYear) {
				return "Today";
			}
			else if ($diff < (b::SecondsInYear*2)) {
				return "Yesterday";
			}
			else if ($diff < (b::SecondsInYear*7)) {
				return date("l", $ts);
			}
			else {
				return date("l, F d, Y");
			}
		}
		
		public static function ago($tm,$rcs = 0) {
		
		    $cur_tm = b::utctime();
		    
		     $dif = $cur_tm-$tm;	
		
		
		    $pds = array('second','minute','hour','day','week','month','year','decade');
		    $lngh = array(1,60,3600,86400,604800,2630880,31570560,315705600);
		    for($v = sizeof($lngh)-1; ($v >= 0)&&(($no = $dif/$lngh[$v])<=1); $v--); if($v < 0) $v = 0; $_tm = $cur_tm-($dif%$lngh[$v]);
		   
		    $no = floor($no); if($no <> 1) $pds[$v] .='s'; $x=sprintf("%d %s ",$no,$pds[$v]);
		    return trim($x) . ' ago';
		}
		
		public static function left($theTime, $level="days+hours+min+sec") {
				$now = strtotime("now");
				$timeLeft = $theTime - $now;
				$theText = '';		
				
				// splut
				$levels = explode("+",$level);	
				 
				if($timeLeft > 0)
				{
				$days = floor($timeLeft/60/60/24);
				$hours = $timeLeft/60/60%24;
				$mins = $timeLeft/60%60;
				$secs = $timeLeft%60;
				
				// check for days
				if(in_array('days',$levels) AND $days > 0) {
					$theText .= $days . " day";						
					if ($days > 1) { $theText .= 's'; }							
				} 
				
				
				if ( in_array('hours',$levels) AND $hours > 0 ) {				
					$theText .= ' '.$hours . " hour";				
					if ($hours > 1) { $theText .= 's'; }				
				}
				
				if (in_array('min',$levels)) {							
					$theText .= ' '.$mins . " min";				
					if ($mins > 1) { $theText .= 's'; }		
				}
				
				if(in_array('sec',$levels)) {
					$theText .= ' '.$secs . " sec";					
					if ($secs > 1) { $theText .= 's'; }
				}
				
						
			}
			
			return $theText;
			
		}
		
		public static function short($str,$len=200,$onwords=true) {
			if ( mb_strlen($str) < $len ) { return $str; }
			if ( !$onwords ) {
				if ( mb_strlen($str) > $len ) {
					return substr($str,0,$len)."...";
				}
			}
			else {
				$words = explode(' ',$str); 
				$final = array();
				$c = 0;
				foreach ( $words as $word ) {
					if ( $c+mb_strlen($word) > $len ) {
						return implode(' ',$final). '...';
					}
					$c += mb_strlen($word);
					$final[] = $word;
				}
			}
		
			return $str;
			
		}
		
		public static function br2nl($string){
			$return=eregi_replace('<br[[:space:]]*/?'.
			'[[:space:]]*>',chr(13).chr(10),$string);
			return $return;
		} 	
		
		public static function show_404($page=b404) {
				
			ob_clean();
			header("HTTP/1.1 404 Not Found",TRUE,404); 
		
			if (!file_exists(b404)) {
				$page = b404;
			} 
		
			
			exit(include($page));
		}
	
		public static function factory($n,$ns='dao') {
			$class = '\\'.$ns.'\\'.$n;
			return new $class;
		}
		
		public static function formatDirName($dir) {
			return rtrim($dir,'/')."/";
		}
		
		public static function convertBase($str,$to=36,$from=10) {
			return (string)base_convert(hexdec($str),$from, $to);
		}
		
	}
	
	/**
	 * global paramater function
	 * @method	p
	 * @param	{string}	key name
	 * @param	{string} 	default value if key != exist [Default: false]
	 * @param	{array}		array to look in [Default: $_REQUEST]
	 * @param   {string}    string to filter on the return
	 */
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
	
		// p raw
		function p_raw($key,$default=false,$array=false) {
			return p($key,$default,$array,FILTER_UNSAFE_RAW);
		}
	
	/**
	 * global path function 
	 * @method	pp
	 * @param	{array}		position (index) in path array
	 * @param	{string}	default 
	 * @param	{string}	filter
	 * @return	{string}	value or false
	 */
	function pp($pos,$default=false,$filter=false) {
			
		// path
		$path = b::_('_bPath');
		
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
	
?>