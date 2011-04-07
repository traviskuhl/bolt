<?php

class Api extends Bolt {

    // accept
    public static $accept = array(''=>'application/xml','json' => 'application/json','xml'=>'application/xml','php'=>'application/php','tar'=>'application/gzip');
    
    // args
    public static $args = false;
    public static $secret = false;
    public static $user = false;
                
    public static function preRoute($page) {              
    
    	// init a page module
    	$mod = Controller::initModule(array('class'=>$page), array(), 'modules');
    	
    	// render our method
    	list($data, $args) = call_user_func(array($mod,self::$args['method']), self::$args);

		// file won't be a file,
		// it will be an array we need to loop 
		// through for interesting parts
		array_walk_recursive($data, function(&$item, $key, $a){
		
			// if we have a string and a module def
			if ( is_string($item) AND substr($item,0,2) == '{%' AND substr($item,-2) == '%}' ) {

				// parse our template
				list($str, $modules, $set) = Controller::parseTemplate($item);
			
				// holder	
				$data = array();
				
				// loop through each module				
				foreach ( $modules as $m ) {
				
					$args = array_merge($a, $m['cfg']);
					
					// mod
			    	$mod = Controller::initModule($m, $a, 'modules');
				
			    	// render our method
			    	list($d) = call_user_func(array($mod, $a['method']), $args);
			    	
			    	// set 
			    	$data[] = $d;
					
				}
			
				// set it 
				if ( count($data) == 1 ) {
					$item = array_shift($data);
				}
				else {
					$item = $data;
				}
				

			}
		}, $args);    	   

        
        // format
        $format = self::$args['format'];
        $ct = self::$accept[$format];
    
        // header
        header("Content-Type:$ct",true,200);
    
		// figure out what we need to do
		if ( $format == 'json' ) {
		
			// header
			header("Content-Type:application/javascript",true,200);
			
			// clean up the array
			array_walk($data,'self::_cleanForJson');
		
			// simple just output the json
			echo json_encode( array( 'stat' => 1, 'results' => $data ) );
		
		}
		else if ( $format == 'php' ) {
		
			// header
			header("Content-Type: text/plain");
			
			// clean up the array
			array_walk($data,'self::_cleanForJson');
			
			// print 
			echo serialize( array('stat' => 1, 'results' => $data) );
		
		}
		else {
		
			// header
			header("Content-Type: application/xml");
		
			// new dom document 
			$this->dom = new DOMDocument('1.0', 'utf-8');
			$this->dom->preserveWhiteSpace = false;
			$this->dom->formatOutput = true;
			
			// results 
			$results = $this->dom->createElement('result');
			$results->setAttributeNode(new DOMAttr('status', '1'));
			
			// root 				
			$root = $this->dom->appendChild( $results );				
			
			// create our root node 
			array_walk($data,array($this,'_mapItemToDom'),$root);		
		
			// print 
			echo $this->dom->saveXML();
		
		}
        
        exit;
        
    }
    
    public static function errorDoc($msg,$hdr=404) {
        
        $format = self::$args['format'];
        $ct = self::$accept[$format];
    
        header("Content-Type:$ct",true,$hdr);
                
        switch($format) {
        
            case 'json':
                exit(json_encode(array('error'=>$msg))); break;
                
            case 'php':
                exit( serialize(array('error'=>$msg ))); break;
                
            default:
                exit('<?xml version="1.0"?><error><message>'.$msg.'</message></error>');
                
        };
        

    }
    
    public static function init() {
        
        // get all headers
        $headers = array_change_key_case(getallheaders());
    
        // check the query string first
        $key = p('key');
        $sig = p('sig');
        $tm = p('time');
        $format = p('format','json');
        
        // what
        $key_name = strtolower(Config::get('api/header-key-name'));
        $sig_name = strtolower(Config::get('api/header-sig-name'));
        $tm_name = strtolower(Config::get('api/header-time-name'));
        
        // now check the headers
        if ( array_key_exists($key_name,$headers) AND !empty($key_name) ) {
            $key = $headers[$key_name];
        }
        
        // sig
        if ( array_key_exists($sig_name,$headers) AND !empty($headers[$sig_name]) ) {
            $sig = $headers[$sig_name];
        }
        
        // sig
        if ( array_key_exists($tm_name,$headers) AND !empty($headers[$tm_name]) ) {
            $tm = $headers[$tm_name];
        }
        
        // accept
        if ( array_key_exists('HTTP_ACCEPT',$_SERVER) AND !empty($_SERVER['HTTP_ACCEPT']) AND in_array($_SERVER['HTTP_ACCEPT'],self::$accept) ) {
            $format = array_search($_SERVER['HTTP_ACCEPT'],self::$accept);
        }
    
        // return them
        self::$args = array(
            'key' => $key,
            'sig' => $sig,
            'time' => $tm,
            'format' => $format,
            'method' => strtolower($_SERVER['REQUEST_METHOD'])
        );
        
        // args 
        return self::$args;
        
    }

		/**
		 * PRIVATE: map the data array to xml
		 * @method	_mapItemToDom
		 * @param	{variable}		item
		 * @param	{string}		key
		 * @param	{ref:object}	root node
		 * @return	{variable}
		 */		 
		private static function _mapItemToDom($item,$key,&$root) {
		
			// check for raw 
			if ( is_array($item) AND  array_key_exists('_raw',$item) ) {
				unset($item['_raw']);
			}
			
			// attribute
			if ( is_array($item) AND $key === '@' ) {
				
				// foreach set as attribute 
				foreach ( $item as $k => $v ) {
					$root->setAttributeNode(new DOMAttr($k,$v));
				}
				
			}									
			
			// items 
			else if ( is_array($item) ) { 	
			
				// is it an int 
				if ( is_int($key) AND array_key_exists('_item',$item) ) {
					$key = $item['_item'];
				}
			
				// create new el 
				$el = $this->dom->createElement($key);										
				
				// append to dom 
				$root->appendChild($el);
				
				// walk it 
				array_walk($item,array($this,'_mapItemToDom'),$el);			
			
			}
			
			// not an item 
			else if ( $key != '_item' ) {
			
				// use cdata 
				$html = false;
			
				// check key for astric 
				if ( $key{0} == '*' ) {
					$html = 'true';
					$key = substr($key,1);
				}
		
				// create new el 
				if ( $html ) {
				
					// create el
					$el = $this->dom->createElement($key);
					
					// append cdata section
					$el->appendChild(new DOMCDATASection($item));
				
				}
				else {
				
					// is null
					if ( is_null($item) ) {
						$item = "";
					}
				
					// el
					$el = $this->dom->createElement($key, htmlentities($item,ENT_QUOTES,'UTF-8',true));
					
				}

				// append to root 
				$root->appendChild($el);					
			
			}

			
		}

	
		/**
		 * PRIVATE: clean up the data array for output as json
		 * @method	_cleanForJson
		 * @param	{ref:variable}	item
		 * @param	{string}		key
		 * @return	{variable}
		 */
		private static function _cleanForJson(&$item,$key) {				
				
			if ( is_array($item) ) {	
			
				// check form stars 
				foreach ( $item as $k => $v ) {
					if ( substr($k,0,1) == '*' ) {
						$item[substr($k,1)] = $v;
						unset($item[$k]);
					}
				}
				
					
				if ( array_key_exists('_item',$item) ) {
					unset($item['_item']);
				}
				if ( array_key_exists('@',$item) ) {					
				}
				if ( array_key_exists('_raw',$item) ) {
					unset($item['_raw']);
				}						
				
				array_walk($item,'self::_cleanForJson');
			}
			else if ( is_bool($item) ) {
				$item = ($item?1:0);
			}
			
		}			

}

?>