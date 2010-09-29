<?php
        
	// framework
	define("bFramework",	"/home/bolt/share/pear/bolt/framework/");
	define("bConfig",		"/home/bolt/config/");
	define("b404",			"/home/bolt/share/htdocs/404.php");
	
	// dev
	define("bDevMode", true ); //( getenv("bolt_framework__dev_mode") == 'true' ? true : false ));
	
	// project
	define("bProject", getenv("bProject"));
	
	// include our Bold file
	require(bFramework . "Bolt.php");
	
	//figure out the project name passed from apache rewrite
	$project = bProject;
		
		// no project we show a 404
		if ( $project === false ) {
			
			// error
			error_log("No project defined");
			
			// exit
			exit( include(b404) );
			
		}
	
	// $class
	$class = Config::get('site/base');

		// no claas
		if ( $class === false OR !class_exists($class, true) ) {
		
			// erro
			error_log("Unable to find project class");
		
			// class
			exit( include(b404) );
			
		}	

	//kick off the project and get back page params
	$class::start();
	
	// pre-route
	$class::prePage();
	
	//get page
	$page = $class::getPage(); 
	
	// pre-route
	$class::preRoute($page);
	
	//kick off our page assembly
	Controller::route($page);
        
?>