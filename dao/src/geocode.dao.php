<?php

namespace Dao;

class geocode extends Webservice {

        protected $data = array(
                'type' => false,
                'f_address'=> false,
                'address_components' => false,
                'city' => false,
                'country' => false,
                'lat'   => false,
                'lng' => false
        );
        
        protected $host = "where.yahooapis.com";
        
        public function __construct($type=false, $cfg=array()) {
        
        	// construct
        	parent::__construct($type,$cfg);
        
        	// set output
        	$this->_set('output', "json-o");
        
        }
        
        public function get($address) {

                
                        //$result = json_decode($this->sendRequest('maps/api/geocode/json?address='.urlencode($address).'&sensor=false'));
                        $result = $this->sendRequest('geocode?q='.urlencode($address).'&appid=PhrssS72&gflags=RA&flags=J'); 

                        
                        if ($result->ResultSet->Error != 0) { 
                                return false;
                        }       
                        
                $result = $result->ResultSet->Results[0];
                
                $neighborhood = (!empty($result->neighborhood)?$result->neighborhood:$result->city);
                                                
                $row = array(
                        'type'=>'address',
                        'f_address'=>(string)$result->line1.', '.$result->line2,
                        'city'=>(string)$result->city,
                        'state'=>(string)$result->state,
                        'zip'=>(string)$result->uzip,
                        'country'=>(string)$result->country,
                        'lat'=>(float)$result->latitude,
                        'lng'=>(float)$result->longitude,
                        'neighborhood'=>(string)$neighborhood
                );
                                        
                // set
                $this->set($row);
        
        }
        
        public function set($row) {
        
                $this->_data = $row;
                        
        }
        
    private function parseCity($result) {
        
                foreach ($result->address_components as $c) {
                
                        if ($c->types[0] == 'locality') { 
                                
                                return $c->long_name;
                        
                        }
                        
                }
                
        }
        
        
        private function parseState($result) {
        
                foreach ($result->address_components as $c) {
                
                        if ($c->types[0] == 'administrative_area_level_1') { 
                                
                                return $c->short_name;
                        
                        }
                        
                }
                
        }
        
        
        private function parseCountry($result) {
        
                foreach ($result->address_components as $c) {
                
                        if ($c->types[0] == 'country') { 
                                
                                return $c->short_name;
                        
                        }
                        
                }
                
        }
        
        
        private function parseLat($result) {
        
                return $result->geometry->location->lat;
                
        }
        
        private function parseLon($result) {
        
                return $result->geometry->location->lng;
                
        }
        
        
}



?>