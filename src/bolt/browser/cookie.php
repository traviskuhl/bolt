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
	public function set($name, $value, $expires=false, $domain=false, $secure=false, $http=false, $path="/") {
		if (is_array($expires)) {
			foreach ($expires as $k => $v) {
				${$k} = $v;
			}
		}

		// domain
		$domain = b::setting("project.cookies.domain")->value;
		$prefix = b::setting("project.cookies.prefix")->value;

		// get name from config
		if ( substr($name, 0, 1) == '$' ) {
			$name = b::_(substr($name, 1));
		}

		// is value a string
		if ( is_array($value) ) {

			// see if this cookie already exists
			if ( p($name, false, $_COOKIE) ) {

				// current
				$cur = $this->get($name);

				    // not an array
				    if (!is_array($cur)) { $cur = array(); }

				// merge the values from the current cookie
				$value = b::mergeArray( $cur, $value );

			}

			// encode it
			$e = base64_encode(json_encode($value));

			// return the padded value
			$value = ":".b::md5($e).$e;

		}

		// expires
		if ($expires AND $expires{0} == '+') {
            $expires = strtotime($expires);
		}
		else if ( $expires AND $expires < b::utctime() ) {
			$expires = b::utctime() + $expires;
		}

		// set it
		return setcookie($prefix.$name, $value, $expires, $path, $domain, $secure, $http);

	}

	public function delete($name) {

		// domain
		$domain = b::setting("project.cookies.domain")->value;
		$prefix = b::setting("project.cookies.prefix")->value;

		// get name from config
		if ( substr($name, 0, 1) == '$' ) {
			$name = b::_(substr($name, 1));
		}

		// set it
		setcookie($prefix.$name, false, time()+1, '/', $domain);

	}

	public function get($name) {

		// prefix
		$prefix = b::setting("project.cookies.prefix")->value;

		// get name from config
		if ( substr($name, 0, 1) == '$' ) {
			$name = b::_(substr($name, 1));
		}

		$name = str_replace(".", "_", $prefix.$name);

		// try to get it
		$cookie = urldecode(array_key_exists($name,	$_COOKIE) ? $_COOKIE[$name] : "");

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