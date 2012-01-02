<?php

namespace bolt\common\dao;
use \b as b;

// account
class sessions extends \bolt\dao\source\mongo {

    protected $table = "sessions";

    public $_useAddedTimestamp = true;
    public $_useModifiedTimestamp = true;


    public function getStruct() {
        return array(
            'id' => array('type' => 'uuid'),
            'account' => array(),
            'created' => array(),
            'expires' => array(),
            'data' => array()
        );    
    }

}