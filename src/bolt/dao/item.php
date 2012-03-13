<?php

namespace bolt\dao;
use \b as b;

/////////////////////////////////////
/// @brief dao item
/////////////////////////////////////
class item implements \Iterator, \ArrayAccess {
    
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
	protected function getStruct() { 
        return array();
    }

	/////////////////////////////////////////////////
	/// @brief is the object loaded
	/// 
	/// @return bool
    /////////////////////////////////////////////////		
	public function loaded() { return $this->_loaded; }

	/////////////////////////////////////////////////
	/// @brief construct a DAO
    /////////////////////////////////////////////////	
	public function __construct($data=array()) {	
    
        // a unique identify for this object        
        $this->_guid = uniqid();			
        
        // if there's a struct 
        $this->_struct = $this->getStruct();
        
        // added and modified
        if ( property_exists($this, '_useAddedTimestamp') AND $this->_useAddedTimestamp === true ) {
            $this->_struct['added'] = array( 'type' => 'added' );
        }
        
        if ( property_exists($this, '_useModifiedTimestamp') AND $this->_useModifiedTimestamp === true ) {
            $this->_struct['modified'] = array( 'type' => 'modified' );							
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

        // figure out if we have a method for this
        if (method_exists($this, "get{$name}")) {
            return call_user_func(array($this, "get{$name}"));
        }

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
			
    			$data[$name] = b::dao($this->_expand[$name][0]);
    			
            	// args
            	$args = $this->_expand[$name][1];

            	   
            	   if (!is_array($args)) { $args = array(); }
            	
                	// args
                	if ( is_array($args) ) {
                    	foreach ( $args as $k => $a ) {
                    		if ( substr($a,0,1) == '$' ) {
                    			$i = substr($a,1);
                    			$args[$k] = (array_key_exists($i, $this->_data) ? $this->_data[$i] : false);
                    		}
                        	}
                        }
                        else {
                		if ( substr($args,0,1) == '$' ) {
                			$i = substr($args,1);
                			$args = (array_key_exists($i, $this->_data) ? $this->_data[$i] : false);
                		}	 	                        
                        }    			
				
				// call our get
				call_user_func_array(array($data[$name], "get"), $args);				
				
			}
				
			// where to place it 
			( array_key_exists($name, $this->_data) ? $this->_data[$name] = $data[$name] : $this->_adjunct[$name] = $data[$name] );

			// unset expandable
			unset($this->_expand[$name]);
			
		}
	
        // struct
        $struct = $this->getStruct();
        
        // call it if it's func
        if (array_key_exists($name, $struct) AND isset($struct[$name]['type']) AND $struct[$name]['type'] == 'func') {
            return call_user_func($struct[$name]['func'], $this);
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
            
            // if they want to modify
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
	   
        // loaded
        $this->_loaded = true;
	
        // figure out if we have a method for this
        if (method_exists($this, "set{$name}")) {
            return call_user_func(array($this, "set{$name}"), $val);
        }	

		// current
		$cur = $this->{$name};	 
		
		// check the struct and see if this set is special
		if (array_key_exists($name, $this->_struct)) {
		      
            // info
            $info = $this->_struct[$name];
		
            // which type
            switch(p('type', false, $info)) {
            
                // json we need to decode
                case 'json':                                         
                    $value = ( is_array($value) ? $value : json_decode($value,true) ); 
                    if ( is_null($value) ) {
                        $value = false;
                    }
                    break;
                    
                // tags need to be turned into
                // a tags array
                case 'tags':                       
                    $this->_expand[$key] = array("\\dao\    ags", array($value)); break;
            
                // user
                case 'user':
                    $this->_expand[$key] = array("\\dao\user", array($value)); break;
            
                // dao
                case 'account':                    
                case 'dao':
            
                    // account
                    if ($info['type'] == 'account') {
                        $info['class'] = '\bolt\common\dao\accounts';
                        $info['args'] = array('id', '$'.$name);                        
                    }
            
                    // class
                    $cl = $info['class'];
            
                    // args
                    $args = p('args', array(), $info);
                            
                    // add to expand
                    $this->_expand[$name] = array($cl, $args);
            
                    // stop
                    break;                    
                    
            };            
		
		}  
	   
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
				if ( isset($this->_data[$ary][$key]) AND !is_array($this->_data[$ary][$key]) ) {
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
		
		// give back
		return $this;

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
                return date($frm, $this->{$ts});
            
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
                                    
            // possessive
            case 'possessive':
            
                // val
                $val = $this->{$args[0]};
            
                // return
                return $val . (substr($val,-1)=='s'?"'":"'s");
                
            // in
            case 'in':
                $val = $args[0];
                $ar = $this->asArray();
                return in_array($val, $ar);            
        };
            
    }
	
	
	/////////////////////////////////////////////////
	/// @brief default set action
	///
	/// @param $data array of data to set	
	/// @return void
	/////////////////////////////////////////////////
	public function set($data){
    
        // loop through and set
        foreach ($data as $name => $value) {
            $this->__set($name, $value);
        }
		
		// give me back
		return $this;
		
	}
	
    public function getDataValue($name, $object=false) {
        return (array_key_exists($name, $this->_data) ? ($object ? new item($this->_data[$name]) : $this->_data[$name]) : false);
    }


	/////////////////////////////////////////////////
	/// @brief normalize the data for insert
	///
	/// @return array
	/////////////////////////////////////////////////	
	public function normalize() {

		// data
		$data = $this->_data;				
			
		$s = $this->getStruct();	
			
        // one last loop
        foreach ($data as $k => $value) {
            if ( !array_key_exists($k, $s) AND is_object($value) AND method_exists($value, 'asArray')) {
                $data[$k] = $value->asArray();
            }
        }
        
        // struct
        foreach ($this->getStruct() as $key => $info) {
        
            // current value
            $value = (array_key_exists($key, $data) ? $data[$key] : false); 
            
            // if we have info lets map it
            $data[$key] = $this->normalizeValid($info, $value, $key, $this);

            // is an object
            if (is_object($data[$key]) AND method_exists($data[$key], 'asArray')) {
                $data[$key] = $data[$key]->asArray();
            }
            
        }

			
		// give back data
		return $data;		
	
	}
	
	   // normalize
	   private function normalizeValid($info, $value, $key, $p) {
          
            // based on data type do the transform
            if ( isset($info['type']) ) {
                
                // which type
                switch ($info['type']) {
                
                	// uuid
					case 'uuid': 
						if ( !$value ) { $value = b::uuid(); } break;
						
					// user
					case 'account':
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
	private function objectify($o) {
        return (is_array($o) ? new item($o) : $o);
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
                if (is_object($value) AND method_exists($value, 'asArray')) {
                    $resp[$key] = $value->asArray();
                }
                else {
                    $resp[$key] = $value;
                }
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
	public function getItemData() {
		return $this->_data;
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
        return $this->objectify($var);
    }


	/////////////////////////////////////////////////
	/// @brief key value of current pointer item
	///
	/// @return value of pointer item
	/////////////////////////////////////////////////	
    public function key() {
        $var = key($this->_data);
        return $this->objectify($var);
    }


	/////////////////////////////////////////////////
	/// @brief go to next item in the set
	///
	/// @return value of next item in set
	/////////////////////////////////////////////////	
    public function next() {
        $var = next($this->_data);    
        return $this->objectify($var);
    }


	/////////////////////////////////////////////////
	/// @brief check if the current value is valid
	///
	/// @return bool if current value is valid
	/////////////////////////////////////////////////	
    public function valid() {
        $var = $this->current() !== false;
        return $this->objectify($var);
    }

    public function count() {
        return count($this->_data);
    }

    // item
    public function item($idx=0) {
    
        // what up
        switch($idx) {
        
            // first item
            case 'first':
                $idx = key(array_slice($this->_data,0,1)); break;
                
            // last item
            case 'last':
                $idx = key(array_slice($this->_data,-1)); break;
                
            // else
            default:
                if (array_key_exists($idx, $this->_data)) { 
                    return $this->objectify($this->_data[$idx]);
                }
        };
       
        // nope
        if (array_key_exists($idx, $this->_data)) {
            return $this->__get($idx);
        }
        else {
            return false;
        }    
    }

    public function offsetSet($offset, $value) {
        $this->_data[$offset] = $value;
    }
    public function offsetExists($offset) {
        return isset($this->_data[$offset]);
    }
    public function offsetUnset($offset) {
        unset($this->_data[$offset]);
    }
    public function offsetGet($offset) {
        return isset($this->_data[$offset]) ? $this->objectify($this->_data[$offset]) : null;
    }

}