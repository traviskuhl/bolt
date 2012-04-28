<?php

namespace bolt\external;
use \b;

// register
b::external()->plug('drib', '\bolt\external\drib');
b::plug('drib', '\bolt\external\drib');

class drib extends \bolt\plugin\singleton {

    private $_settings = array();
    
    public function __construct() {
        
        // the cid
        $cid = "bolt.drib.settings";
        
        // if we don't
        if (($settings = apc_fetch($cid)) == false) {
            
            // get them 
            $str = file_get_contents("/var/drib/settings.txt");
            
            // settings
            $settings = array();
            
            // expldoe and loop
            foreach (explode("\n", $str) as $line) {
                if (strpos($line, '|') !== false) { 
                    list($var, $val) = explode("|", trim($line));
                    $settings[$var] = $val;
                }
            }
            
            // store
            apc_store($cid, $settings);
            
        }
        
        // set it 
        $this->_settings = $settings;
        
    }

    // get
    public function get($name) {
        $name = str_replace(".", "_", $name);
        return (array_key_exists($name, $this->_settings) ? $this->_settings[$name] : false);
    }
    
    public function __get($name) {
        return $this->get($name);
    }

}