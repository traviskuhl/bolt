<?php

abstract class Dao extends DaoHelpers implements \Iterator {

	// struct
	private $_struct = array();
	private $_changes = array();
	private $_event = array();
	private static $_callbacks = array( 'set' => array() );
	
	// protected
	protected $_data = array();	
	protected $_adjunct = array();
	protected $_items = array();
	protected $_expand = array();
	protected $_useAddedTimestamp = false;
	protected $_useModifiedTimestamp = true;
	protected $_cache = false;
	
	// public stuff for paging
	public $_total = 0;
	public $_pages = 0;
	public $_page = 1;
	public $_per = 20;
	public $_start = 1;
	public $_end = 20;
	
	
	/////////////////////////////////////////////////
	/// @brief construct a DAO
    /////////////////////////////////////////////////	
	public function __construct($type=false,$cfg=array()) {		
		
		// cahce
		$this->_cache = Cache::singleton();
		$this->_event = Events::singleton();	
		
		// if there's a struct 
		$this->_struct = $this->getStruct(); 	
		
		// is empty array
		// fall back to data and schema 
		// for backwards compatability
		if ( count($this->_struct) == 0 AND isset($this->data) AND is_array($this->data) ) {
			
			// copy data to _data and delete
			foreach ( $this->data as $key => $value ) {
				
				// data
				$this->_data[$key] = $value;
				
				// struct
				$htis->_struct[$key] = array();
			
			}
			
			// unset
			unset($this->data);
			
			// schema
			if ( isset($this->schema) AND is_array($this->schema) ) {
				
				// loop
				foreach ( $this->schema as $key => $value ) {
					$this->_struct[$key] = $value;
				}
				
				// unset
				unset($this->schema);
				
			}
			
		}
		

		// added and modified
		if ( count($this->_struct) > 0 AND $this->_useAddedTimestamp === true ) {
			$this->_struct['added'] = array( 'type' => 'added' );
		}

		if ( count($this->_struct) > 0 AND $this->_useModifiedTimestamp === true ) {
			$this->_struct['modified'] = array( 'type' => 'modified' );							
		}
		
		// struct
		foreach ( $this->_struct as $key => $x ) {
			if ( !array_key_exists($key, $this->_data) ) {
				$this->_data[$key] = false;
			}
		}
		
		// if get param
		if ( $type == 'get' ) {		
			call_user_func_array(array($this,'get'), (array)$cfg);
		}
		else if ( $type == 'set' ) {
			$this->set($cfg);
		}
		else if ( is_string($type) AND method_exists($this, $type) ) {
			call_user_func_array(array($this,$type), (array)$cfg);
		}
		
	}
	
	
	/////////////////////////////////////////////////
	/// @brief return the object construct
	/// 
	/// @return array with object construct
    /////////////////////////////////////////////////	
	protected function getStruct() { return array(); }
	
	
	/////////////////////////////////////////////////
	/// @brief MAGIC get a property from data array
	/// 
	/// @param name name of the property to get from
	///        the data array
	/// @return value if property exists. false
	///         if property does not exist
    /////////////////////////////////////////////////
	public function __get($name) {

        // data
        $data = array_merge($this->_data, $this->_adjunct);

		// check if it's something we need to expand
		if ( array_key_exists($name, $this->_expand) ) {
			
			// check if expand 0 is a function
			// if so we just execute it
			// instead of instanciating it
			if ( is_callable($this->_expand[$name][0]) ) {
			
				// call our get
				$data[$name] = call_user_func_array($this->_expand[$name][0], $this->_expand[$name][1]);							
			
			}
			else {
				
				// name
				$data[$name] = new $this->_expand[$name][0];
				
				// call our get
				call_user_func_array(array($data[$name], "get"), $this->_expand[$name][1]);				
				
			}
				
			// where to place it 
			( array_key_exists($name, $this->_data) ? $this->_data[$name] = $data[$name] : $this->_adjunct[$name] = $data[$name] );

			// unset expandable
			unset($this->_expand[$name]);
			
		}
	

		// if it exists
		if ( array_key_exists($name, $data) ) {		
				
			// objectify
			if ( is_array($data[$name]) ) {
				return $this->objectify($data[$name]);
			}
			
			// plain
            return $data[$name];										
            
		}
	
        // check for _
        else if ( mb_strpos($name,'_') !== false ) {
 
		 		// modifiers
				$modify = array(
					'_encode_' => function($v) { return htmlentities($v, ENT_QUOTES, "utf-8"); },
					'_decode_' => function($v) { return html_entity_decode($v, ENT_QUOTES, "utf-8"); },
					'_ucfirst_' => function($v) { return ucfirst($v); },
					'_toupper_' => function($v) { return strtoupper($v); },
					'_tolower_' => function($v) { return strtolower($v); },
					'_number_foramat_' => function($v) { return number_format($v); },
					'_possesive_' => function($v) { return \b::possesive($v); }
				);				
				
				foreach ( $modify as $str => $func ) {	
					if ( strpos($name, $str) !== false ) {
						$n = str_replace($str, "", $name);					
						return call_user_func($func, p_raw($n, false, $data));
					}
				}
 
            // explode out 
            $parts = explode('_',$name);           
            
            // value
            $val = $data;
            $i = 0;
            
            // loop
			foreach ( $parts as $p ) {				
					
				// cur
				if ( is_object($val) AND $val->{$p} !== false ) {
					$val = $val->{$p};
				}
				else if ( is_array($val) AND isset($val[$p]) ) {
					$val = $val[$p];
				}
				else {
					$val = false; break;
				}
								
			}
	        	
	        // return
	        return ( is_array($val) ? $this->objectify($val) : $val);
			
        }
        else if ( isset($this->{"_{$name}"}) ) {
        	return $this->{"_{$name}"};
        }
		else if ( isset($this->_struct[$name]['cast']) ) {
		
			// false
			$r = false;
			
			if ( $this->_struct[$name]['cast'] == 'array' ) {
				$r = array();
			}
			else {
				settype($r, $this->_struct[$name]['cast']);
			}
			
			return $r;
			
		}
		
        
        // nope
        return false;

	}	
	
	
	/////////////////////////////////////////////////
	/// @brief MAGIC set a property from data array
	/// 
	/// @param name name of the property to set in
	///        the data array
	/// @param val value to set property to
	/// @return null
    /////////////////////////////////////////////////	
	public function __set($name,$val) {

		// current
		$cur = $this->{$name};	   
	   
	   	// find some data 
		if ( array_key_exists($name, $this->_data)) {
		 	$this->_data[$name] = $val;
		}
			
        // check for _
        else if ( mb_strpos($name,'_') ) {
            
			// explode out 
            $parts = explode('_',$name);
            
            // set parts
            $ary = $parts[0];
            $key = $parts[1];
            
            // what
            if ( !isset($this->_data[$ary]) ) {
            	$this->_data[$ary] = array();
            }
            
        	// check if they want a key if the array
			if ( isset($parts[2]) ) {
				if ( !is_array($this->_data[$ary][$key]) ) {
					$this->_data[$ary][$key] = array();
				}
        	            	
      			$this->_data[$ary][$key][$parts[2]] = $val;
        	
			}
			else {
				// just return the array
				$this->_data[$ary][$key] = $val;				
			}
            
        }
        else {
        	$this->_data[$name] = $val;
        }
        
		// add it 
		if ( isset($this->_trackChanges) AND $this->_trackChanges == true AND $name != 'changelog' ) {
		
            // make sure we have an array
            if (!is_array($this->_changes)) { $this->_changes = array(); }
            
            // see if cur is an object
            // if yes try to normalize it
            if (is_object($cur)) {     
                $this->__mapNormalize($cur, $name, $this->_struct);
            }
            
            // still an object try to get an id
            if (is_object($cur)) {
            
                // to an array
                $cur = $cur->asArray();
                
                // if id use that 
                if (array_key_exists('id', $cur)) {
                    $cur = $cur['id'];
                }
                
            }   
            
            // change
            if (is_array($val) AND is_array($cur) ) {
            	if (count(array_diff($val, $cur)) > 0) { 
	            	$this->_changes[$name] = array( 'new' => $val, 'old' => $cur );
	            }
            }
            else if ($val != $cur) {            
       			$this->_changes[$name] = array( 'new' => $val, 'old' => $cur );
       	    }
   			
		}

	}		
	
	
	
	
	/////////////////////////////////////////////////
	/// @brief MAGIC return object as json array
	/// 
	/// @return data array as json string
    /////////////////////////////////////////////////	
	public function __toString() {
		return json_encode( $this->asArray() );
	}	


	/////////////////////////////////////////////////
	/// @breif attach a callback to a dao
	///
	/// @return 
	/////////////////////////////////////////////////
	public static function attach($event, $dao,  $func, $params=array(), $pri=1) {
	
		// self
		self::$_callbacks[$event][] = array( 'dao' => $dao, 'func' => $func, 'params' => $params, 'priority' => $pri );
		
		// order
		uasort(self::$_callbacks[$event], function($aa,$bb){		
			$a = $aa['priority'];
			$b = $bb['priority'];
		    if ($a == $b) {
		        return 0;
		    }
		    return ($a < $b) ? -1 : 1;		
		});
		
	}
		
	
	/////////////////////////////////////////////////
	/// @breif default get action
	///
	/// @return full data array
	/////////////////////////////////////////////////
	public function get() {
		return $this->_data;
	}
	
	public function getDisplay($key) {	
	   if (array_key_exists($key, $this->_struct) AND array_key_exists('display', $this->_struct[$key])) {	   
	       return $this->_struct[$key]['display'];
	   }
	   return $key;
	}
	
	public function getMapped($key, $val) {	
	   if (array_key_exists($key, $this->_struct) AND array_key_exists('map', $this->_struct[$key]) ) {	   
            if (is_array($val) AND count($val) == 0 ) {
                return "";
            }
			else if ( (is_array($this->_struct[$key]['map']) AND is_callable($this->_struct[$key]['map']) ) OR is_callable($this->_struct[$key]['map'])) {
				return call_user_func($this->_struct[$key]['map'], $val, $this);			
			}
			else if (array_key_exists($val, $this->_struct[$key]['map'])) {
				return $this->_struct[$key]['map'][$val];
			}
	   }
	   return $val;	
	}
	
	/////////////////////////////////////////////////
	/// @brief default set action
	///
	/// @param $data array of data to set	
	/// @return void
	/////////////////////////////////////////////////
	public function set($data){
		
		// needs to be an array
		if ( !is_array($data) ) { return; }
		
		// data 
		$this->_data = $data;						
			
		// normalize
		array_walk($data, array($this, '__mapSet'), $this->_struct);											
								
		// give back data
		foreach ($data as $key => $val ) {
			$this->_data[$key] = $val;
		}		
		
		// fire any attached callbacks
		foreach ( self::$_callbacks['set'] as $cb ) {			
			if ( $cb['dao'] == array_pop(explode('\\',get_class($this))) ) {
				call_user_func_array($cb['func'], array($this, $data, $cb['params']));							
			}
		}		
		
	}

		// map normalized strucs
		private function __mapSet(&$item, $key, $struct) {
								
			// struct
			if ( isset($struct[$key]) ) {
	
				// children
				if ( isset($struct[$key]['children']) AND is_array($item) ) {
					array_walk($item, array($this, '__mapSet'), $struct[$key]['children']);
				}
				else {
					$item = call_user_func(array($this, '__mapSetFunc'), $struct[$key], $item, $key);				
				}
					
			}		
		
		}

		// the validate function
		private function __mapSetFunc($info, $value, $key) {      
			
			// is it set
			if (isset($info['cast'])) {

				// arrays shoudln't be false
	        	if ($info['cast'] == 'array' AND $value === false) {
	        		$value = array();
	        	}
	        	else if ($info['cast'] == 'int') {
	            	$value = (int)$value;
	        	}
	        	else {	            
	            	settype($value, $info['cast']);                    
	            }		
	            
			}
          
            // based on data type do the transform
            if ( isset($info['type']) ) {
                
                // which type
                switch ($info['type']) {
                
                    // json we need to decode
                    case 'json':                             
                    
                        $value = ( is_array($value ) ? $value : json_decode($value,true) ); 
                        if ( is_null($value) ) {
                        	$value = false;
                        }
                        break;                                          
                        
                    // tags need to be turned into
                    // a tags array
                    case 'tags':                       
                    	$this->_expand[$key] = array("\\dao\\tags", array($value)); break;
                        
					// user
					case 'user':
                    	$this->_expand[$key] = array("\\dao\user", array($value)); break;
                        
                    // dao
                    case 'dao':
                    
                    	// class
                    	$cl = "\\dao\\{$info['class']}";
                    	
                    	// args
                    	$args = p('args', array(), $info);
                    	
                        	// args
                        	if ( is_array($args) ) {
	                        	foreach ( $args as $k => $a ) {
	                        		if ( substr($a,0,1) == '$' ) {
	                        			$i = substr($a,1);
	                        			$args[$k] = $this->{$i};
	                        		}
 	                        	}
 	                        }
 	                        else {
                        		if ( substr($args,0,1) == '$' ) {
                        			$i = substr($args,1);
                        			$args = $this->{$i};
                        		}	 	                        
 	                        }
	                        	
	                        // add to expand
	                        $this->_expand[$key] = array($cl, $args);
                    	
                    	break;
                    	
                    // expand
                    case 'expand':
                    
                    	// args
                    	$args = p('args', array(), $info);                    
                    	
                    	// expand
                    	$this->_expand[$key] = array($info['func'],array($this, $args));
                    	
                    	// stop
                    	break;
                                                    
                        
                        
/*
                    // tags need to be turned into
                    // a tags array
                    case 'tags':                            
                        $value = new \dao\tags('set',$value); break;
                        
					// user
					case 'user':
						$value = new \dao\user('get', array($value)); break;
                        
                    // dao
                    case 'dao':
                   
                    
                    	// class
                    	$cl = "\\dao\\{$info['class']}";
                    	
                    	// args
                    	$args = p('args', array(), $info);
                    	
                        	// args
                        	if ( is_array($args) ) {
	                        	foreach ( $args as $k => $a ) {
	                        		if ( substr($a,0,1) == '$' ) {
	                        			$i = substr($a,1);
	                        			$args[$k] = $obj->{$i};
	                        		}
 	                        	}
 	                        }
 	                        else {
                        		if ( substr($args,0,1) == '$' ) {
                        			$i = substr($args,1);
                        			$args = $obj->{$i};
                        		}	 	                        
 	                        }
	                        	
                    	
                    	// o
                    	$o = new $cl();
                    	
                    	// call
                    	call_user_func_array(array($o, 'get'), $args);
                    	
                    	// value
                        $value = $o; break;
                        
*/
                        
                    
                    // datetime
                    case 'timestamp':
                    	
                    	// if no value
                    	if ( !$value ) { break; }                        
                    	
                    	// break
                    	break;
                    	
                    
                }
                                   
            }
            
            return $value;
            
		}

	/////////////////////////////////////////////////
	/// @brief normalize the data for insert
	///
	/// @return array
	/////////////////////////////////////////////////	
	public function normalize() {
	
		// data
		$data = $this->_data;	
		
		// walk through struct and make sure each key exist
		foreach ($this->_struct as $key => $info) {
		  if (!array_key_exists($key, $data)) {
		      $data[$key] = false;
		  }
		}
			
		// normalize
		array_walk($data, array($this, '__mapNormalize'), $this->_struct);						
			
		// give back data
		return $data;		
	
	}
	
		// map normalized strucs
		private function __mapNormalize(&$item, $key, $struct) {
			
			// the validate function
			$validate = function($info, $value, $key, $p) {      
              
	            // based on data type do the transform
	            if ( isset($info['type']) ) {
	                
	                // which type
	                switch ($info['type']) {
	                
	                	// uuid
						case 'uuid':						
							if ( !$value ) { $value = b::getUuid(); } break;
							
						// user
						case 'user':
							$value = (is_object($value)?$value->id:$value); break;
	                 
	                    // json we need to decode
	                    case 'json': 
	                        $value = json_encode($value); break;
	                        
	                    // tags need to be turned into
	                    // a tags array
	                    case 'tags':                            
	                        $value = (string)$value; break; 
	                        
	                    // dao
	                    case 'dao':
	                    	if ( is_object($value) ) {
	                    		$id = p('id','id',$info);                            	
								$value = $value->{$id}; 								
							}
							break;
							
						// timestsamp
						case 'timestamp':
						
	                    	// check user for a tzoffset
					        $u = Session::getUser();
					        
					        // offset
					        if ( $u AND $u->profile_tzoffset ) {
					        	$value -= $u->profile_tzoffset;
					        }							
						
							break;
							
						// added
						case 'added':
							if ( $value === false ) {
								$value = b::utctime();
							}							
							break;
							
						// modified
						case 'modified':
							$value = b::utctime(); break;
							
						// changelog
						case 'changelog':						
                            if (!is_array($value)) { $value = array(); }
                            $ch = $p->getChanges();
                            if (count($ch) == 0 ) { break; }
							$value[] = array(
                                'ts' => b::utctime(),
                                'changes' => $ch,
                                'by' => (b::_("_account") ? b::_("_account")->id : false)
                            ); 
                        break;
	                    
	                };
	                
	            }
	            
	            // is deafult
	            if ( isset($info['default']) AND $value === false ) {
	            	$value = $info['default'];
	            }
	            
	            // cast
	            if ( isset($info['cast']) ) {
	            	            
	            	// cast is array and value is default
	            	if ($info['cast'] == 'array' AND $value === false) {
	            		$value = array();
	            	}
	            	else if (is_object($value)) {
                        $value = $value->asArray();
	            	}
	            	else {	            
		            	settype($value, $info['cast']);
		            }		            
		            
	            }
	            
	            return $value;
	            
			};			
			
			// struct
			if ( isset($struct[$key]) ) {
		
				// validate
				$item = $validate($struct[$key], $item, $key, $this);
	
				// children
				if ( isset($struct[$key]['children']) AND is_array($item) ) {
					array_walk($item, array($this, '__mapNormalize'), $struct[$key]['children']);
				}
					
			}		
			
			return $item;
		
		}
		
    public function getChanges() { 
        return $this->_changes;
    }

	/////////////////////////////////////////////////
	/// @brief turn an array into an object
	///
	/// @param array the array to turn into an object
	/// @return stdclass object
	/////////////////////////////////////////////////	
	private function objectify($array) {
	
		if(!is_array($array)) { return $array; }
			
		$object = new DaoMock();
		if (is_array($array)) {
		  foreach ($array as $name => $value) {
		        $object->$name = $this->objectify($value);
		  }
	      return $object; 
		}
	    else {
	      return false;
	    }


	}		
	
	/////////////////////////////////////////////////
	/// @brief print object as an array
	///
	/// @param key return only single key as array
	/// @return data object value as array
	/////////////////////////////////////////////////	
	public function asArray($adjunct=false) {
	
		// get the scheme
		$resp = array();
		
		// item type
		if ( count($this->_struct) > 0 AND !$this->_items ) {
				
			// loop through the struct
			foreach ( $this->_struct as $key => $info ) {
				
				// val
				$val = $this->__get($key);
				
				// see if it's a dao type
				// if it is we need to expand
				if ( isset($info['type']) AND in_array($info['type'],array('dao','user','tags')) AND is_object($val) ) {
					$resp[$key] = $val->asArray();
				}
				else if ( is_object($val) AND method_exists($val, "asArray") ) {
					$resp[$key] = $val->asArray();
				}
				else {
					$resp[$key] = $val;
				}
			
			}
		
		
			// adjunct 
			if ( $adjunct ) {
				foreach ( $this->_adjunct as $key => $val ) {
					if ( is_object($val) AND method_exists($val, "asArray") ) {
						$resp[$key] = $val->asArray();
					}
					else {
						$resp[$key] = $val;
					}				
				}
			}
			
		}
		
		// list type
		else {
		
			// loop through each item and add to object
			foreach ( $this->_items as $key => $item ) {
				$resp[$key] = (is_object($item) ? $item->asArray() : $item);
			}
		
		}
		
		// give back the clean array
		return $resp;
	
		
	}
	
	// to array
	public function toArray($name) {
		$resp = ( $this->$name ? $this->{$name}->asArray() : array() );
		return (is_array($resp) ? $resp : array());
	}		
	
	/////////////////////////////////////////////////
	/// @brief get data array
	/// 
	/// @return data aray;
	/////////////////////////////////////////////////
	public function getData() {
		return $this->_data;
	}
	
	
	/////////////////////////////////////////////////
	/// @brief get schema array
	/////////////////////////////////////////////////
	public function getSchema() {
		return $this->schema;
	}	
	
	/////////////////////////////////////////////////
	/// @brief set the pager information for list functions
	///
	/// @param total total number of pages
	/// @param page current page
	/// @param per number of items per page
	/// @return void
	/////////////////////////////////////////////////
	protected function setPager($total,$page,$per) {
				
		$this->_total = (int)$total;
		$this->_page = $page;
		$this->_per = $per;
		
		// pages
		$this->_pages = ($page>0?ceil($total/$per):1);
	
		// sttart
		$this->_start = ( ($page-1) * $per )+1;
		$this->_end = ($this->_start + $per) - 1;
		
			if ( $this->_end > $this->_total ) {
				$this->_end = $this->_total;
			}
	
	}
	
	/////////////////////////////////////////////////
	/// @brief get the # of the first item in page set
	///
	/// @return formated number of start item
	/////////////////////////////////////////////////	
	public function getStart() {
		return number_format((double)$this->_start);
	}


	/////////////////////////////////////////////////
	/// @brief get the # of the last item in page set
	///
	/// @return formated number of last item
	/////////////////////////////////////////////////	
	public function getEnd() {
		return number_format((double)$this->_end);
	}

	
	/////////////////////////////////////////////////
	/// @brief get total number of items in set
	///
	/// @return formated number of total items in set
	/////////////////////////////////////////////////		
	public function getTotal() {
		return number_format((double)$this->_total);
	}	
	
	
	/////////////////////////////////////////////////
	/// @brief get total number of pages
	///
	/// @return formated number of total items in set
	/////////////////////////////////////////////////		
	public function getPages() {
		if ( $this->_pages==0 ) { return 1; }	
		return number_format((double)$this->_pages);
	}	
	
	
	/////////////////////////////////////////////////
	/// @brief get total number of pages
	///
	/// @return formated number of total items in set
	/////////////////////////////////////////////////		
	public function getPage() {
		if ( $this->_page==0 ) { return 1; }
		return number_format((double)$this->_page);
	}		
	

	/////////////////////////////////////////////////
	/// @brief get total number of pages
	///
	/// @return formated number of total items in set
	/////////////////////////////////////////////////		
	public function getPer() {
		return number_format((double)$this->_per);
	}		
	
	/////////////////////////////////////////////////
	/// @brief get the next page number in the page set
	///
	/// @return int of next page number
	/////////////////////////////////////////////////		
	public function nextPage() {
		if ( $this->_page == $this->_pages ) {
			return false;
		}
		return $this->_page + 1;
	}
	
	
	/////////////////////////////////////////////////
	/// @brief get the prev page number in the page set
	///
	/// @return int of the prev page 
	/////////////////////////////////////////////////		
	public function prevPage() {
		if ( $this->_page == 1 ) {
			return false;
		}
		return $this->_page - 1;		
	}
	
	
	/////////////////////////////////////////////////
	/// @brief array containing all pages in the page set
	///
	/// @return array of page numbers
	/////////////////////////////////////////////////		
	public function range() {
		if ( $this->_total == 0 ) { return array(); }
		return range(1,$this->_pages);
	}

}


abstract class DaoHelpers {
	
	/////////////////////////////////////////////////
	/// @brief MAGIC call a function
	/// 
	/// @return mixed
    /////////////////////////////////////////////////		
    public function __call($name,$args) {
    	
    	// return
    	$r = false;
    	
    	// what to return
    	switch($name) {
    	
             // short
                case 'short':
                
                	// conver arg 0 info value
                	$args[0] = $this->{$args[0]};
                       
					// ask b::short
					return call_user_func_array("b::short", $args);
                                
                // date
                case 'date':
                
                        // give r
                        $ts = $args[0];
                        $frm = (isset($args[1])?$args[1]:'m/d/Y');
                        
                                // if not a ts
                                if ( !$this->{$ts} ) {
                                        return false;
                                }
                        
                        // give it 
                        return date($frm,$this->{$ts});
                        
				// ago
				case 'ago':
				
					return b::ago($this->{$args[0]});
                        
                // decode
                case 'decode':
                                return html_entity_decode($this->{$args[0]},ENT_QUOTES,'utf-8');
                                
                // decode
                case 'encode':
                                return htmlentities($this->{$args[0]},ENT_QUOTES,'utf-8',false);
        
        		// number
        		case 'number':
        			return number_format((int)$this->{$args[0]});
        
                // pop
                case 'push':
                        
                        // get some stuff
                        $ary = $this->{$args[0]};
                        $val = $args[1];
                        $key = (isset($args[2])?$args[2]:false);
                        
                        // if ary === false we assume it's just empty
                        if ( $ary === false ) {
                                $ary = array();
                        }
                        
                        // is object
                        if ( is_object($ary) AND method_exists($ary,'asArray') ) {
                                $ary = $ary->asArray();
                        }
                        
                                // need it to be an array
                                if( !is_array($ary) ) {
                                        return false;
                                }
                
                        // add it
                                if ( $key ) {
                                        $ary[$key] = $val;
                                }
                                else {
                                        $ary[] = $val;
                                }
                                
                                // reset
                                $this->{$args[0]} = $ary;
                                
                                // return array
                                return $ary;
                        
                // keyed arary
                case 'keyArray':
                        
                        // field
                        $val = $args[0];
                        $key = (isset($args[1])?$args[1]:'id');
        
                        // opts
                        $array = array();
                        
                        // loop
                        foreach ( $this->_items as $itm ) {
                                $k = $itm->$key;
                                $v = $itm->$val;
                                $array[$k] = $v;
                        }
                        
                        return $array;  
                        
				// shuffle
				case 'shuffle':
					
					// get the array
					if ( isset($args[0]) ) {
						
						// get the array
						$ary = $this->{$args[0]};
						
						// is it an object
						if ( is_object($ary) ) {
							$ary = $ary->asArray();
						}
						
						// is an array
						if ( is_array($ary) ) {
							shuffle($ary);
						}
	
						return new DaoMock($ary);
					
					}
					else {
					
						// shuffle
						shuffle($this->_items);					
						
						// give it 
						return $this;
					
					}

				// filter
				case 'filter':
					
					// key and value
					$key = str_replace(".", "_", $args[0]);
					$val = $args[1];
					$fa = (isset($args[2]) ? $args[2] : false);
					
					// loop through our items
					// and start picking out what doesn't match
					foreach ( $this->_items as $k => $item ) {											
						if ( is_callable($val) ) {
							if ( !call_user_func($val, $item->{$key}, $fa) ) {
								unset($this->_items[$k]);
								$this->_total--;						
							}
						}						
						else if ( $item->{$key} != $val ) {
							unset($this->_items[$k]);
							$this->_total--;
						}					
					}
					
					// return just in case they want it that way also
					return $this->_items;
				
					break;
					
				// sort
				case 'sort':
					
					$func = $args[0];
					
					$ary = $this->_items;
					
					usort($ary, $func);
								
					$this->_items = $ary;
					
					return $this;
                        
				// in or inarray
				case 'in':
				case 'inarray':
				case 'in_array':
					$ar = $this->_items;
					$val = $args[0];
					if (count($args) > 1) {
						$ar = $this->toArray($args[0]);
						$val = $args[1];
					}
					
					return in_array($val, $ar);
                        
				// slice
				case 'slice': 
					
				
					// is 0 the name or a number
					if ( is_array($this->{$args[0]}) ) {
						return array_slice($this->{$args[0]}, $args[1], $args[2]);
					}
					else if ( is_array($this->_items) ) {
					
						$_args = array($this->_items);
											
						// slice	
						$this->_items = call_user_func_array('array_slice', array_merge($_args, $args));
					
						// return this
						return $this;
					
					}
				
					break;
					
				case 'implode':
				
					if (count($args) == 1) {
						$ary = $this->_items;
    	                $str = $args[0];					
					}
					else {
	                    $ary = $this->{$args[0]};                    
    	                $str = $args[1];
    	            }
                                       
					// object
                    if (is_object($ary) AND method_exists($ary, 'asArray')) { $ary = $ary->asArray(); }
					
                        // is it an array
                        if ( !is_array($ary) ) { return false; }                    
                        
					return implode($str, $ary);					
					
					break;

				case 'explode':
                    $str = $this->{$args[0]};                    
                    $sep = $args[1];
                                       					
                        // is it an array
                        if ( !is_string($str) ) { return false; }                    
                        
					return explode($sep, $str);					
					
					break;
					
				// reverse
				case 'reverse':
					
					// utens
					$this->_items = array_reverse($this->_items, true);
					
					return $this;
				
					break;
                        
                // unset
                case 'unset': 
                        
                        $ary = $this->{$args[0]};
                        $key = (isset($args[1])?$args[1]:false);
                        
                        // object
                        if (is_object($ary) AND method_exists($ary, 'asArray')) { $ary = $ary->asArray(); }
                        
                        // is it an array
                        if ( !is_array($ary) ) { return $ary; }
                
                        // unset it 
                        if ( $key  ) {
                                        unset($ary[$key]);
                        }
                        else {
                                        $ary = false;
                        }
                
                        // reset
                        $this->{$args[0]} = $ary;
                
                        // return
                        return $ary;
    			
    			// possessive
    			case 'possessive':
    				
    				// val
    				$val = $this->{$args[0]};
    				
    				// return
    				return $val . (substr($val,-1)=='s'?"'":"'s");
    	
    	};
    
    
    }

	/////////////////////////////////////////////////
	/// @brief get item value at given index
	///
	/// @param idx index number to check for value
	/// @return value at given index
	/////////////////////////////////////////////////		
	public function item($idx=0) {	
		if ($idx === false OR !is_array($this->_items) OR (is_array($this->_items) AND count($this->_items) == 0) ) { 
			return false; 
		}
		else if ( $idx == 'first' ) {
			return array_shift( array_slice($this->_items,0,1) );
		}
		else if ( $idx == 'last' ) {
			return array_shift( array_slice($this->_items,-1) );
		}
		else if ( array_key_exists($idx,$this->_items) ) {		
			return $this->_items[$idx];
		}
		else {
			return false;
		}
		
	}


	/////////////////////////////////////////////////
	/// @brief reset pointer to first item in set
	///
	/// @return void
	/////////////////////////////////////////////////	
    public function rewind() {
        reset($this->_items);
    }


	/////////////////////////////////////////////////
	/// @brief get the current item pointer 
	///
	/// @return value of current pointer item
	/////////////////////////////////////////////////	
    public function current() {
        $var = current($this->_items);
        return $var;
    }


	/////////////////////////////////////////////////
	/// @brief key value of current pointer item
	///
	/// @return value of pointer item
	/////////////////////////////////////////////////	
    public function key() {
        $var = key($this->_items);
        return $var;
    }


	/////////////////////////////////////////////////
	/// @brief go to next item in the set
	///
	/// @return value of next item in set
	/////////////////////////////////////////////////	
    public function next() {
        $var = next($this->_items);
        return $var;
    }


	/////////////////////////////////////////////////
	/// @brief check if the current value is valid
	///
	/// @return bool if current value is valid
	/////////////////////////////////////////////////	
    public function valid() {
        $var = $this->current() !== false;
        return $var;
    }


    public function exists($key) {
        return array_key_exists($key, $this->_items);
    }

}

//
class DaoMock extends DaoHelpers implements Iterator {


	protected $_items = array();
	
	public function __construct($data=array()) {	
		$this->_items = $data;		
	}
	
	public function __set($name,$val) {		
		$this->_items[$name] = $val;		
	}
	public function __get($name) {
		if ( $name == 'total' ) {
			return count($this->_items);
		}	
		
		if ( array_key_exists($name,$this->_items) ) {
			if ( is_array($this->_items[$name])) {			
				return $this->objectify($this->_items[$name]);
			}
			else {
				return $this->_items[$name];
			}
		}
        else if ( mb_strpos($name,'_') !== false ) {
 
            // explode out 
            $parts = explode('_',$name);
            
            // value
            $val = $this->_items;
            $i = 0;
            
            // loop
			foreach ( $parts as $p ) {				
					
				// cur
				if ( is_object($val) AND $val->{$p} !== false ) {
					$val = $val->{$p};
				}
				else if ( is_array($val) AND isset($val[$p]) ) {
					$val = $val[$p];
				}
				else {
					$val = false; break;
				}
								
			}
	        	
	        // return
	        return $val;
			
        }		
		
		return false;
	}
	public function asArray() {
		$a = array();
		$it = $this->_items;
				
		foreach ( $it as $k => $v ) {
			if ( is_object($v) AND method_exists($v, 'toArray') ) {
				$v = $v->asArray();
			}
			$a[$k] = $v;
		}
		return $a;
	}
	
	// to array
	public function toArray($name) {
		return ( $this->$name ? $this->{$name}->asArray() : array() );
	}	
	
	public function exists($key) {
		$it = $this->_items;		
		if ( isset($it[$key]) ) {
			return true;
		}
		return false;
	}
	
	/////////////////////////////////////////////////
	/// @brief turn an array into an object
	///
	/// @param array the array to turn into an object
	/// @return stdclass object
	/////////////////////////////////////////////////	
	private function objectify($array) {
	
		if(!is_array($array) ) {
			return $array;
		}
		
		
		if ( is_array($array) AND is_numeric(key($array)) ) {
			foreach ( $array as $i => $v ) {
				$array[$i] = $this->objectify($v);
			}
			return $array;
		}
	
		$object = new DaoMock();
		if (is_array($array) && count($array) > 0) {
		  foreach ($array as $name => $value) {
		        $object->$name = $this->objectify($value);
		  }
	      return $object; 
		}
	    else {
	      return false;
	    }


	}		

	
}



?>