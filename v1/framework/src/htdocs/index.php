<?php
        
	// framework
	define("bFramework",	"/home/bolt/share/pear/bolt/framework/");
	
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
			$class = "Bolt";
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