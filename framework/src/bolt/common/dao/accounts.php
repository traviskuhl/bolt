<?php

namespace bolt\common\dao;
use \b as b;

// account
class accounts extends \bolt\dao\source\mongo {

    protected $table = "accounts";

    public function getStruct() {
        return array(
            'id' => array('type' => 'uuid'),
            'firstname' => array(),
            'lastname' => array(),
            'email' => array(),
            'password' => array(),
            'data' => array()
        );    
    }

}