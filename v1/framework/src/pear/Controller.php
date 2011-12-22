<?php

class Controller {

	// static
	static $modules = array();
	static $embeds = array( 'css' => array(), 'js' => array() );
	static $id = 1;
	static $globals = array();
	
	// callback
	static $_callbacks = array( );

	static function registerGlobal($key, $val) {
		self::$globals[$key] = $val;
	}
	
	static function registerCallback($for, $func) {
		if ( !array_key_exists($for, self::$_callbacks) ) { self::$_callbacks[$for] = array(); }
		self::$_callbacks[$for][] = $func;
	}

	static function executeCallbacks($for, $args=false) {
		if ( array_key_exists($for, self::$_callbacks) ) {
			foreach ( self::$_callbacks[$for] as $func ) {
				$args = call_user_func($func, $args);
			}
		}
		return $args;
	}

	static function includeModule($name, $type='modules')
	{

		// modules root
		$root = Config::get('paths/'.$type);
		$path = false;
		$bolt = false;

		//echo "/home/bolt/share/pear/bolt/$type/$name/$name.render.php"; die;
		//echo "$root/$name/$name.render.php"; die;


		// does it exist
		if ( file_exists("$root/$name/$name.render.php") ) {
			$path = "$root/$name/$name.render.php";
		}
		else if ( file_exists("/home/bolt/share/pear/bolt/$type/$name/$name.render.php") ) {
				$path = "/home/bolt/share/pear/bolt/$type/$name/$name.render.php";
				$bolt = true;
			}

//		var_dump($path, "$root/$name/$name.render.php"); die;

		// if path
		if ( $path ) {
			include_once $path;
		}

		// return
		return array($path, $bolt);

	}


	static function route($page) {
	
		// get our ctx
		$ctx = b::_('_bContext', $page);
	
		// what route to take
		switch($ctx) {
		
			// ajax page
			case 'ajax':
				self::ajax(); break;
				
			// xhr
			case 'xhr':
				self::xhr(); break;
				
			// rss
			case 'rss':
				self::rss(); break;
				
			// json
			case 'json':
				self::json($page); break;
				
			// xml
			case 'xml':
				self::xml($page); break;	
				
			// html
			default:
				self::html($page);	
				
		};
		
	}

	static function xhr() {

		// module
		$module = p('_bModule');
		$origin = p('origin');
		$type = p('_bType', 'modules');
		
		// set it
		$mod = self::initModule(array('class'=>$module), array(), $type);

		// check the module
		if ( !$mod ) {
			b::show_404();
		}

		$r = $mod->render( self::$globals );

		// parse tokens
		$html = self::replaceTokens($r->html, $r->args, true);

		// parse tmpl
		list($html, $js) = self::parseHtmlForScriptTags($html);

		// output
		header("Content-Type:text/javascript");

		// print
		exit( json_encode( array("stat" => 1, 'html' => $html, 'bootstrap' => array( 'c' => @$page->args['bodyClass'], 'js' => $js ) ) ) );

	}

	static function ajax()
	{

		// module
		$module = p('_bModule');
		$origin = p('origin');
		$type = p('_bType', 'modules');

		// set it
		$mod = self::initModule(array('class'=>$module), array(), $type);

		// check the module
		if ( !$mod OR ( $mod AND !method_exists($mod, 'ajax') ) ) {
			b::show_404();
		}

		// call xhr
		$resp = $mod->ajax();

		// check what to
		if ( p('xhr') !== 'true' AND $origin ) {
			exit(header("Location:".$origin));
		}
		else {

			// header
			header("Content-Type:text/javascript");

			// prit
			exit( json_encode( array_merge(array('stat'=>1), $resp) ) );

		}

	}


	static function rss()
	{

		// module
		$module = p('_module');
		$type = p('_type', 'modules');

		// set it
		$mod = self::initModule(array('class'=>$module), array(), $type);

		// check the module
		if ( !$mod ) {
			show_404();
		}

		// call xhr
		$resp = $mod->rss();

		// header
		header("Content-Type:application/rss+xml");

		// prit
		exit( $resp );



	}
	
	static function json($page, $project=false) {
		
		// try to figure out the
		// path of the template
		$base = Config::get('paths/pages', true);

		// if there's a project
		// we need to add it's folder
		if ( $project ) {
			$base .= "{$project}/";
		}

		// include the page
		$pg = self::initModule(array('class'=>$page), array('parent'=>array(), 'template'=>array()), 'pages');

			// now make sure the page exists
			if ( !$pg ) {
				b::show_404();
			}

		// render
		// html
		$o = $pg->render( self::$globals );

		// no object something is wrong
		// so lets fake one
		if ( !is_object($o) ) {
			$o = new StdClass();
			$o->html = array();
			$o->status = 0;
		}

		// proper header
		header("Content-Type: text/javascript", true, 200);
	
		// return the json
		exit( json_encode(array('status'=>$o->status, 'response' => $o->html)) );
	
	}


	static function html($page, $project=false) {

		// try to figure out the
		// path of the template
		$base = Config::get('paths/pages', true);

		// if there's a project
		// we need to add it's folder
		if ( $project ) {
			$base .= "{$project}/";
		}

		// include the page
		$pg = self::initModule(array('class'=>$page), array('parent'=>array(), 'template'=>array()), 'pages');

		// now make sure the page exists
		if ( !$pg ) {
			b::show_404();
		}

		// render
		// html
		$o = $pg->render( self::$globals );

		// no object something is wrong
		// so lets fake one
		if ( !is_object($o) ) {
			$o = new StdClass();
			$o->html = "";
			$o->args = array();
		}

		// args
		$args = $o->args;
		
			// bodyclass
			if ( isset($args['bodyClass']) AND !is_array($args['bodyClass']) ) {
				$args['bodyClass'] = explode(' ', $args['bodyClass']);
			}
			else if (!isset($args['bodyClass'])) {
				$args['bodyClass'] = array();
			}
			
			// logged
			if ( b::_("_logged") ) {
				$args['bodyClass'][] = 'logged';
			}
		
			// bodyclass
			$args['bodyClass'] = implode(" ",$args['bodyClass']);
		
		// if args tells us not to 
		// render the global
		if ( isset($args['_bNoGlobalTemlate']) AND $args['_bNoGlobalTemlate'] == true ) {
			$global = $o;
		}
		else {

			// body
			$args['_body'] = $o->html;
	
			// render the template
			$global = self::renderTemplate( Config::get('site/globalTemplate'), $args, $base );
			
		}
		
		// render html
		self::executeCallbacks('renderHtml');

		// added embed lists
		$args['cssEmbeds'] = Controller::getEmbedList('css', true);
		$args['jsEmbeds'] = Controller::getEmbedList('js', true);

		// since this is special, we need to do it
		// ourself
		$global = self::replaceTokens($global->html, $args, true);

		// try getting our hostname
		$hn = apc_fetch("server_hostname");

		// get it
		if ( !$hn ) {
			$hn = trim(`hostname`);
			apc_store('server_hostname', $hn);
		}

		// render time
		$rt = round(microtime(true) - START, 4) . ' seconds';

		// add to global
		$global .= "\n<!-- {$hn} - ".date('r')." - {$rt} -->";

		// now replace the body
		exit( $global );

	}
	
	public static function renderPage($file, $p_args=array()) {
	
		// what function to call
		$func = "self::renderTemplate";
	
		// what content to do
		switch(b::_('_bContext')) {
			
			// json
			case 'json':
				$func = 'self::renderJson'; break;
		
			// xml
			case 'xml':
				$func = "self::renderXml"; break;
											
		};
	
		return call_user_func($func,
			$file,
			$p_args,
			Config::get('paths/pages')
		);
	}
	
	public static function renderJson($data, $p_args=array()) {
		
		// what is self
		$self = __CLASS__;	
		
		$f = function(&$item, $key, $a){		
			if ( is_string($item) AND substr($item,0,2) == '{%' AND substr($item,-2) == '%}' ) {			
				// render a string an reset it
				$item = call_user_func("{$a[0]}::renderString", $item, $a[1])->html;
			}
			if ( is_array($item) ) {
				foreach ( $item as $k => $v) {
					if ( is_string($v) ) {
						$a[2]($item[$k], $k, $a);
					}
					else if ( is_array($v)) {
						array_walk($item[$k], $a[2], $a);
					}
				}
			}
		};
		
		// file won't be a file,
		// it will be an array we need to loop 
		// through for interesting parts
		if ( is_array($data) ) {
			array_walk($data, $f, array($self, $p_args, $f));
		}
		else {
			$f($data, $f, array($self, $p_args, $f));
		}
	
		// return it
		$o = new StdClass();

		// set some stuff
		$o->html = $data;
		$o->args = $p_args;
		$o->status = p('status', 0, $p_args);
		
		// give back
		return $o;
		
	}

	public static function renderModule($file, $p_args=array(), $base=false) {
		
		// get object 
		$o = self::renderTemplate($file, $p_args, $base);
		
		// execute callback
		return self::executeCallbacks("renderModule", $o);
		
	}

	public static function renderTemplate($x__file, $p_args=array(), $base=false)
	{

		// try to figure out the
		// path of the template
		if ( !$base ) {
			$base = Config::get('paths/modules', true);
		}

		$base = "/" . trim($base, '/') . "/";

		// check for page
		if ( strpos($x__file, '.php') === false ) {
			$x__file .= ".template.php";
		}

		// get any globals
		$p_args = array_merge( self::$globals, $p_args );

		// start ob buffer
		ob_start();

		// define all
		foreach ( $p_args as $k => $v ) {
			$$k = $v;
		}

		// include the page
		include $base.$x__file;

		// stop
		$page = ob_get_contents();

		// clean
		ob_clean();

		// give it back
		return self::renderString($page, $p_args, $base);

	}

	public static function renderString($str, $p_args=array(), $base=false) {
	
		// parse the template
		list($template, $modules, $tmpl_settings) = self::parseTemplate($str, $p_args);

		// now we loop through each
		// one of the modules and create it
		foreach ( $modules as $i => $m ) {

			// set it
			$mod = self::initModule($m, array('parent'=>$p_args));

			// no mod continue
			if ( !$mod ) { continue; }

			// set our args
			$mod->_args = $m['cfg'];

			// html
			$o = $mod->render($m['cfg']);

			if ( $o ) {		

				if (is_array($o)) {
					$template = $o;
				}				
				else if ( is_array($o->html) ) {
					$template = $o->html;
				}
				else {

					// render
					$template = str_replace("<module{$m['id']}>", $o->html, $template);
	
					// add to list
					$modules[$i]['html'] = $o->html;
					
				}
				
			}

			$modules[$i]['ref'] = $mod;

		}

		// template
		if ( is_string($template) ) {
			$template = self::replaceTokens($template, $p_args);
		}

		// object
		$o = new StdClass();

		// set some stuff
		$o->html = $template;
		$o->modules = $modules;
		$o->args = $p_args;


		// give it back
		return $o;

	}
	
	public function buildModule($module, $args=array()) {

		// set it
		$mod = self::initModule(array('class'=>$module), array('parent'=>self::$globals));

		// no mod continue
		if ( !$mod ) { continue; }

		// set our args
		$mod->_args = $args;

		// html
		return $mod->render($args);	
	
	}

	public function replaceTokens($template, $d_args, $cleanup=false)
	{

		// args
		$args = array();

		// take out any object
		foreach ( array_merge( self::$globals, $d_args ) as $k => $v ) {
				$args[$k] = $v;
		}

		// parse the template
		if ( preg_match_all('#\{\$([a-z0-9\.\-\|\_\s\'\(\)\/]+)\}#i', $template, $matches, PREG_SET_ORDER) ) {

			// loop through each
			foreach ( $matches as $match ) {
				
				// func
				$func = false;
				
				// see if there's a function
				if ( preg_match("#([a-z\_]+)\(([^\)]+)\)#i", $match[1], $m) ) {
					$func = $m[1];
					$match[1] = $m[2];				
				}
				
				// check for a defualt
				$key = array_shift(explode('|', $match[1]));
				$default = "";
				$value = false;

				// no default
				if ( strpos($match[1], '|') !== false ) { $default = array_pop(explode('|', $match[1])); }

				// first see if it's in args alone
				if ( array_key_exists($key, $args) ) {
					$value = $args[$key];
				}

				// url
				else if ( strpos($key, 'url.') !== false ) {
					$value = Config::url(str_replace('url.', '', $key));
				}

				// see if there are . in the name
				else if ( strpos($key, '.') !== false ) {

						// break apart
						$parts = explode('.', $key);

						// value
						$k = $args;

						// loop through
						foreach ( $parts as $p ) {										
							if ( $k AND is_array($k) AND array_key_exists($p, $k) ) {
								$k = $k[$p];
							}
							else if ( $k AND is_object($k) AND $k->{$p} !== false ) {	
								$k = $k->{$p};
							}
							else { $k = $default; break; }
						}

						// replace it
						$value = $k;

				}

				// if value go ahead and replace
				if ( $value === false and $default and !$cleanup ) {
					$value = $default;
				}
				
				// function
				if ( function_exists($func) ) {
					$value = $func($value);
				}

				// replace
				if ( $value !== false AND is_string($value) ) {
					$template = str_replace($match[0], $value, $template);
				}

			}

		}

		// parse the template
		if ( preg_match_all('#\{\@([a-z\_]+)\(([^\)]+)\)\}#i', $template, $matches, PREG_SET_ORDER) ) {
			foreach ( $matches as $match ) {		
				if ( function_exists($match[1]) ) {
					$template = str_replace($match[0], $match[1]($match[2]), $template);			
				}
			}			
		}

		// if cleanup remove any leftovers
		if ( $cleanup AND preg_match_all('#\{\$([a-z0-9\.\-\|\_\s\'\(\)]+)\}#i', $template, $matches, PREG_SET_ORDER) ) {
			foreach ( $matches as $match ) {		
				$template = str_replace($match[0], "", $template);			
			}
		}

		// re
		return $template;

	}

	public function initModule($mod, $args, $type='modules') {

		if (is_string($mod)) {
			$mod = array('class'=>$mod);
		}

		// include
		list($_m, $bolt) = self::includeModule($mod['class'], $type);

		// nope
		if ( !$_m ) {
			return;
		}

		// if bolt we need to namespace
		if ( $bolt == true ) {
			$mod['class'] = '\bolt\\'.$mod['class'];
		}
		else if ( $type == 'modules') {
			$mod['class'] = '\modules\\'.$mod['class'];
		}

		// go for it
		$m = new $mod['class']($args);

		// check for embeds
		if ( property_exists($m, 'embeds') ) {

			// get htem
			$embeds = $m::$embeds;

			// if js
			if ( isset($embeds['js']) ) {
				foreach ( $embeds['js'] as $name => $file ) {
					self::addEmbed('js', $name, $file);
				}
			}

		}

		// save
		self::$modules[$mod['class']] = $m;

		// give back
		return $m;

	}

	public function parseTemplate($string, $p_args=array())
	{

		// modules
		$modules = array();
		$settings = array();

		// lets start parsing
		if ( preg_match_all("/\{%\s?([a-z\_]+)(\([^\)]+\))?\s?%\}/", $string, $match, PREG_SET_ORDER) ) {

			// loop through each module and
			// figure out what is there
			foreach ( $match as $m ) {
				if ( substr($m[0], 1) != '$' ) {

					$i = self::$id++;

					// add the module
					$mod = array( 'id' => $i, 'class' => trim($m[1]), 'cfg' => array( '_isChild' => true) );

					// isset
					if ( isset($m[2]) ) {

						// args
						$args = trim($m[2], '()');

						// has at least 1 :
						if ( strpos($args, ':') !== false ) {
							foreach ( explode(',', $args) as $a ) {
							
								// get key value
								list($k, $v) = explode(':', $a);
								
								// check for {$ in the v, which means we should replace any tokens
								if ( substr($v,0,2) == '{$' ) { $v = self::replaceTokens($v, $p_args, true); }
								
								// mod
								$mod['cfg'][trim($k)] = trim($v);
							}
						}
						else {
							$mod['cfg'] = unserialize(base64_decode($args));
						}

					}

					// modules
					$modules[] = $mod;

					// replace
					$string = preg_replace("/".preg_quote($m[0], '/')."/", "<module{$i}>", $string, 1);

				}
			}

		}

		// lets start parsing
		if ( preg_match_all("/\{\#([a-zA-Z\_]+)\:\s?([^\#]+)\#\}/", $string, $match, PREG_SET_ORDER) ) {
			foreach ( $match as $m ) {
				$settings[trim($m[1])] = trim($m[2]);
				$string = str_replace($m[0], "", $string);
			}
		}

		// just give back
		return array($string, $modules, $settings);

	}


	public static function parseHtmlForScriptTags($body)
	{

		// need to remove comments
		$body = preg_replace(array("/\/\/[a-zA-Z0-9\s\&\?\.]+\n/", "/\/\*(.*)\*\//"), " ", $body);

		// javascript
		$jsInPage = preg_match_all("/((<[\s\/]*script\b[^>]*>)([^>]*)(<\/script>))/i", $body, $js);


		// if yes remove
		if ( $jsInPage ) {
			$body = preg_replace("/((<[\s\/]*script\b[^>]*>)([^>]*)(<\/script>))/i", "", $body);
		}

		// give back
		return array($body, @$js[3]);

	}

	public static function addEmbed($type, $name, $project=false, $file) {
		if ( $project !== false ) {
			self::$embeds[$type][] = array($name => "{$project}/{$type}/{$file}" );
		}
		else {
			self::$embeds[$type][] = array($name => $file);
		}
	}
	
	public static function getEmbedList($type) {
		
		// config
		$config = array();
		
			// check config;
			if ( Config::get('embeds/'.$type) ) {
				$config = Config::get('embeds/'.$type);
			}
		
		// list
		$list = array();
		
		// print the list
		foreach (  array_merge(self::$embeds[$type], $config) as $name => $file ) {
			
			if ( $type == 'css' ) {
				if (bDevMode) { $file .= '?.r='.time(); }
				$list[] = "<link rel='stylesheet' href='".(bDevMode ? "/assets/" : '' ).$file."' type='text/css'>";
			}
			else if ( $type == 'js') {			
				$n = key($file);
				if (bDevMode) { $file[$n] .= '?.r='.time(); }				
				$list[$n] = array('file' => (bDevMode ? URI."/assets/" : '' ) . $file[$n]);
			}
		}
	
		return ($type=='css' ? implode("\n",$list) : json_encode($list) );
	
	}

	public static function printCfg($a, $rtn=false) {
		$c = base64_encode(serialize($a));
		if ( $rtn ) {
			return $c;
		}
		else {
			echo $c;
		}
	}

}


?>