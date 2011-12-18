<?php 

// bolt
namespace bolt;

// this is a special class. it's
// not a plugin and can't
// be inited
use \b as b;


// helpers
class helpers {
	
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
	
		
	public static function uuid($parts=5, $prefix=false) {
	
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
