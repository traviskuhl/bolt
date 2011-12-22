<?php

namespace Ext;

require(bFramework . "Ext/Fb/facebook.php");

////////////////////////////////
/// @breif facebook integration
////////////////////////////////
class Fb {

	// properties
	public $fb = false;
	public static $instance = false;
	private $loged = false;
	private $user = false;

	////////////////////////////////
	/// @breif singleton constructor
	////////////////////////////////
	public static function singleton() {

		if ( !self::$instance ) {
			$class = __CLASS__;
			self::$instance = new $class();
		}

		// return
		return self::$instance;

	}


	////////////////////////////////
	/// @breif private constrcutor
	////////////////////////////////
	private function __construct() {

		// fb 
		$this->fb = new \Facebook(array('appId'=>\Config::get('site/fb-key'),'secret'=>\Config::get('site/fb-secret'),'cookie'=>true));		

		// user
		$this->user = \Config::get('user');

			// loged
			if ( $this->user !== false ) {
				$this->loged = true;
			}		

		$this->fbSession = $this->fb->getSession();

		// attach some events
		$events = \Events::singleton();

	}


	////////////////////////////////
	/// @breif event dispatcher
	////////////////////////////////	
	public function eventDispatch($type,$args) {

		// switch me on type
		switch($type) {


		}

	}
	
	public function api() {
		
		// try it
		$resp = call_user_func_array(array($this->fb, "api"), func_get_args());
		
		// resp
		return new FbResponse($resp);
	
	}


	/////////////////////////////////////
	/// @breif magic caller that passes 
	///		   any undefined methods to 
	///		   to api_client
	/////////////////////////////////////	
	public function __call($name,$args=false) {	
		if ( method_exists($this->fb,$name) ) {
			return call_user_func_array(array($this->fb,$name),$args);
		}
	}

	////////////////////////////////
	/// @breif shortcut to stream_publish
	////////////////////////////////	
	public function streamPublish($args) {

		// push through
		$this->fb->api_client->stream_publish(
			$args['message'],
			json_encode($args['attachment']),
			json_encode($args['action'])
		);		

	}	

	////////////////////////////////
	/// @breif shortcut to get_loggedin_user
	////////////////////////////////
	public function getUser() {
		return @$this->fb->getUser();
	}


	////////////////////////////////
	/// @breif shortcut to notifications_sendEmail
	////////////////////////////////	
	public function sendEmail($subject,$message,$to=false) {

		// no to
		if ( !$to ) {
			$to = $this->user['fbtoken'];
		}

		// no an array
		if ( !is_array($to) ) {
			$to = array($to);
		}

		// send it 		
        $this->fb->api_client->notifications_sendEmail(
                $to,						// userids
                $subject,
                $message,					// plaintext
                nl2br($message,true)		// fbml version. since we don't have one we use the same
            );   		

	}

}


// fbrsp
class FbResponse extends \Dao implements \Iterator {

	//error 
	private $session = true;
	private $error = false;

	// construct
	public function __construct($resp) {
	
		// what up with the response
		if ( !$resp OR isset($resp['error']) ) {
		
			// there's an error
			$this->error = $resp['error']['message'];

			
			// is it an oauth erro
			if ( $resp['error']['type'] == 'OAuthException' OR $resp['error']['type'] == 'invalid_token' ) {				
				$this->session = false;
			}
			
			// done here
			return;
			
		}
		
		if ( isset($resp['data']) ) {
			$this->_items = $resp['data'];
		}
		else {
			$this->_data = $resp;	
		}
	
	}
	
	public function hasSession() {
		return $this->session;
	}

}


?>