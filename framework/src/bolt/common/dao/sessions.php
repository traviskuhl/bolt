<?php

namespace bolt\common\dao;
use \b as b;

// account
class sessions extends \bolt\dao\source\mongo {

    protected $table = "sessions";

    public function getStruct() {
        return array(
            'id' => array('type' => 'uuid'),
            'sid' => array(),
            'account' => array(),
            'created' => array(),
            'expires' => array(),
            'data' => array()
        );    
    }

}