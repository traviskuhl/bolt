<?php

namespace bolt\render;
use \b;


// render
b::render()->plug('mustache', '\bolt\render\mustache');

// mustache
class mustache extends \bolt\plugin\singleton {

	private $eng;

	public function __construct() {		

		// include
		require bRoot.'/vendor/Mustache/Autoloader.php';
		\Mustache_Autoloader::register(bRoot.'/vendor/');

		// engine
		$this->eng = new \Mustache_Engine;


	}

	public function render($str, $vars=array()) {	
		return $this->eng->render($str, $vars);
	}


}