<?php

namespace bolt\dao;
use \b as b;

/////////////////////////////////////
/// @brief dao item
/////////////////////////////////////
class item implements \Iterator {
    
    // guid
    public $_guid;
    
    // private stuff
    private $_struct = array();
    private $_data = array();
    private $_expand = array();
    private $_loaded = false;
    private $_parent = false;
    
    // some protected stuff
	protected $_useAddedTimestamp = false;
	protected $_useModifiedTimestamp = false;    
    public $_adjunct = array();
    
    
	/////////////////////////////////////////////////
	/// @brief return the object construct
	/// 
	/// @return array with object construct
    /////////////////////////////////////////////////	
	protected function getStruct() { $this->_struct; }
	
	public function loaded() { return $this->_loaded; }

	/////////////////////////////////////////////////
	/// @brief construct a DAO
    /////////////////////////////////////////////////	
	public function __construct($parent=false, $data=array()) {	
	
	   $this->_guid = uniqid();			
		
		if (is_object($parent)) {
		
		
		$this->_parent = $parent;    	
    	
    		// if there's a struct 
    		$this->_struct = $parent->getStruct();
    		
    		// added and modified
    		if ( property_exists($parent, '_useAddedTimestamp') AND $parent->_useAddedTimestamp === true ) {
    			$this->_struct['added'] = array( 'type' => 'added' );
    		}
    
    		if ( property_exists($parent, '_useModifiedTimestamp') AND $parent->_useModifiedTimestamp === true ) {
    			$this->_struct['modified'] = array( 'type' => 'modified' );							
    		}
        }
				
		// struct
		foreach ( $this->_struct as $key => $x ) {
			if ( !array_key_exists($key, $this->_data) ) {
				$this->_data[$key] = false;
			}
		}

        // set some sdata
        if (count($data) > 0) {
            $this->set($data);			
        }
		
	}
	
	
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
        $data = array_merge($this->_data, $this->_adjunct);;

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
			
    			$data[$name] = b::dao($this->_expand[$name][0]);
				
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
					'_ago_' => function($v) { return b::ago($v); },
				);				
				
				foreach ( $modify as $str => $func ) {	
					if ( strpos($name, $str) !== false ) {
						return call_user_func($func, $data[str_replace($str, "", $name)]);
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
		if ( isset($this->_trackChanges) AND $this->_trackChanges == true AND $val != $cur AND $name != 'changelog' ) {
			$this->_changes[$name] = array( 'new' => $val, 'old' => $cur );
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
	/// @brief MAGIC call a function
	/// 
	/// @return mixed
    /////////////////////////////////////////////////		
    public function __call($name,$args) {
    	
    	//var_dump($this->_parent, method_exists($this->_parent, $name) ); 
    	
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
                        
				// in or inarray
				case 'in':
				case 'inarray':
				case 'in_array':
					
					return in_array($args[0], $this->_items);
                        
				// slice
				case 'slice': 
					
				
					// is 0 the name or a number
					if ( is_array($this->{$args[0]}) ) {
						return array_slice($this->{$args[0]}, $args[1], $args[2]);
					}
					else if ( is_array($this->_items) ) {
						
						// slice	
						$this->_items = array_slice($this->_items, $args[0], $args[1]);
					
						// return this
						return $this;
					
					}
				
					break;
                        
                // unset
                case 'unset': 
                        
                        $ary = $this->{$args[0]};
                        $key = (isset($args[1])?$args[1]:false);
                        
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
	/// @brief default get action
	///
	/// @return full data array
	/////////////////////////////////////////////////
	public function get() {
		return $this->_data;
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
		
		// loaded
		$this->_loaded = true;
		
		// data 
		$this->_data = $data;						
			
		// normalize
		array_walk($data, array($this, '__mapSet'), $this->_struct);																
								
		// give back data
		foreach ($data as $key => $val ) {
			$this->_data[$key] = $val;
		}		
		
		// map our struct
		foreach ($this->_struct as $k => $v) {
		  if (!array_key_exists($k, $this->_data)) {
		      $this->_data[$k] = false;
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
                    	$cl = $info['class'];
                    	
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
                    	
                    	// stop
                    	break;
                    
                    // func
                    case 'func':
                       $this->_expand[$key] = array($info['func'], array($this)); break;
                        
                        
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
							if ( !$value ) { $value = b::uuid(); } break;
							
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
							$value = $p->_changes; break;
	                    
	                };
	                
	            }
	            
	            // is deafult
	            if ( isset($info['default']) AND $value === false ) {
	            	$value = $info['default'];
	            }
	            
	            // cast
	            if ( isset($info['cast']) ) {
	            	settype($value, $info['cast']);
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
		
		}

	/////////////////////////////////////////////////
	/// @brief turn an array into an object
	///
	/// @param array the array to turn into an object
	/// @return stdclass object
	/////////////////////////////////////////////////	
	private function objectify($array) {
	
		if(!is_array($array)) { return $array; }
			
		if (is_array($array)) {
            $o = false;
            if (is_string(key($array))) {
                $o = new item($this->_parent, $array);
            }
            else {
                $o = new stack();
                foreach ($array as $i) {
                    $o->push($i);
                }
            }
            return $o;
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
				
        // loop through the data 
        foreach ($this->_data as $key => $value) {
            if (array_key_exists($key, $this->_struct)) {
            
                // info
                $info = $this->_struct[$key];
                
				// val
				$val = $this->__get($key);
				
				// see if it's a dao type
				// if it is we need to expand
				if ( isset($info['type']) AND in_array($info['type'],array('dao','user','tags')) ) {
					$resp[$key] = $val->asArray();
				}
				else if ( is_object($val) AND method_exists($val, "asArray") ) {
					$resp[$key] = $val->asArray();
				}
				else {
					$resp[$key] = $val;
				}            
            
            }
            else {
                $resp[$key] = $value;
            }        
        }
						
    	// loop through the struct
    	foreach ( $this->_struct as $key => $info ) {
            if (!array_key_exists($key, $resp)) {
                    
				// val
				$val = $this->__get($key);
				
				// see if it's a dao type
				// if it is we need to expand
				if ( isset($info['type']) AND in_array($info['type'],array('dao','user','tags')) ) {
					$resp[$key] = $val->asArray();
				}
				else if ( is_object($val) AND method_exists($val, "asArray") ) {
					$resp[$key] = $val->asArray();
				}
				else {
					$resp[$key] = $val;
				}             
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
				
		// give back the clean array
		return $resp;
	
		
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
	/// @brief reset pointer to first item in set
	///
	/// @return void
	/////////////////////////////////////////////////	
    public function rewind() {
        reset($this->_data);
    }


	/////////////////////////////////////////////////
	/// @brief get the current item pointer 
	///
	/// @return value of current pointer item
	/////////////////////////////////////////////////	
    public function current() {
        $var = current($this->_data);
        return $var;
    }


	/////////////////////////////////////////////////
	/// @brief key value of current pointer item
	///
	/// @return value of pointer item
	/////////////////////////////////////////////////	
    public function key() {
        $var = key($this->_data);
        return $var;
    }


	/////////////////////////////////////////////////
	/// @brief go to next item in the set
	///
	/// @return value of next item in set
	/////////////////////////////////////////////////	
    public function next() {
        $var = next($this->_data);
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


}