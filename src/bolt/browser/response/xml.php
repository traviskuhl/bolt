<?php

namespace bolt\render;
use \b as b;
use \DOMAttr;
use \DOMDocument;
use \DOMCDATASection;

b::render()->plug('xml', '\bolt\render\xml');

// json
class xml extends \bolt\plugin\singleton {

    // accept or header
    public static $accept = array(
        100 => 'application/xml'
    );
    
    // content type
    public $contentType = "application/xml";
    
    //
    public function render($view) {
    
		// print 
		$g = new xmlGenerator($view->getData());    
    
        // set it
        return $g->render();
    
    }
    
}

class xmlGenerator {

    public function __construct($data) {
    
		// new dom document 
		$this->dom = new DOMDocument('1.0', 'utf-8');
		$this->dom->preserveWhiteSpace = false;
		$this->dom->formatOutput = true;		
					
		// create our root node 
		array_walk($data, array($this,'_mapItemToDom'), $this->dom);		    
		  
    }
    
    public function render() {
        return $this->dom->saveXML();    
    }

	/**
	 * PRIVATE: map the data array to xml
	 * @method	_mapItemToDom
	 * @param	{variable}		item
	 * @param	{string}		key
	 * @param	{ref:object}	root node
	 * @return	{variable}
	 */		 
	private function _mapItemToDom($item,$key,&$root) {
	
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

			// create el
			$el = $this->dom->createElement($key);
		
			// value
			if (array_key_exists('_value', $item)) {
			
                // value
                $el->nodeValue = $item['_value'];

    			// foreach set as attribute 
    			if (array_key_exists('@', $item)) {
        			foreach ( $item['@'] as $k => $v ) {
        				$el->setAttributeNode(new DOMAttr($k,$v));
        			}	           		
                }

    			// append to root 
    			$root->appendChild($el);    

			}
			else {
    		
    			// create new el 
    			$el = $this->dom->createElement($key);										
    			
    			// append to dom 
    			$root->appendChild($el);
    			
    			// walk it 
    			array_walk($item,array($this,'_mapItemToDom'),$el);			
    			
            }
		
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

}