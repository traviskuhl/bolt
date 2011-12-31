<?php

namespace bolt;
use \b as b;

// plug
b::plug('cookie', '\bolt\cookie');

class cookie extends plugin\singleton {

    ////////////////////////////////////////////////
    /// @brief add url params to a url
    ///
    /// @param $url base url
    /// @param $params array of params to add
    /// 
    /// @return string url with additional params
    ////////////////////////////////////////////////
	public function set($name, $value, $expires=false, $domain=false) {
	
		// domain
		$domain = b::config()->get("cookies/domain");
	
		// get name from config
		if ( substr($name, 0, 1) == '$' ) {
			$name = b::_(substr($name, 1));
		}
	
		// is value a string
		if ( is_array($value) ) {
		
			// see if this cookie already exists
			if ( p($name, false, $_COOKIE) ) {
				
				// merge the values from the current cookie
				$value = b::mergeArray( $this->get($name), $value );
				
			}
			
			// encode it
			$e = base64_encode(json_encode($value));
		
			// return the padded value
			$value = ":".b::md5($e).$e;
			
		}
		
		// expires
		if ( $expires AND $expires < b::utctime() ) {
			$expires = b::utctime() + $expires;
		}
	
		// set it 
		setcookie($name, $value, $expires, '/', $domain);
	
	}
	
	public function delete($name) {

		// domain
		$domain = b::_('site/cookieDomain');

		// get name from config
		if ( substr($name, 0, 1) == '$' ) {
			$name = b::_(substr($name, 1));
		}

		// set it 
		setcookie($name, false, time()+1, '/', $domain);
	
	}
	
	public function get($name) {

	
		// get name from config
		if ( substr($name, 0, 1) == '$' ) {
			$name = b::_(substr($name, 1));
		}
	
		// try to get it 
		$cookie = urldecode(p($name, false, $_COOKIE));
	
			// if we don't have it, stop
			if ( !$cookie ) { return false; }
			
		// see if the first val is a :
		if ( $cookie{0} == ':' ) {
							
			// make sure we're goof
			$cookie = $this->getDecodedCookie($cookie);
			
		}
	
		// return it 
		return $cookie;
	
	}		
	
	public function getDecodedCookie($cookie) {

		// split it 
		$sig = substr($cookie, 1, 32);
		
		// now the value
		$e = substr($cookie, 33);
		
		// make sure we're goof
		return ( b::md5($e) == $sig ? json_decode(base64_decode($e), true) : false );
		
	}


}