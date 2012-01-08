<?php

namespace bolt\common\dao;
use \b as b;

// account
class accounts extends \bolt\dao\source\mongo {

    protected $table = "accounts";
    
    // added mod
    public $_useAddedTimestamp = true;
    public $_useModifiedTimestamp = true;

    public function getStruct() {
        return array(
            'id' => array('type' => 'uuid'),
            'username' => array(),
            'firstname' => array(),
            'lastname' => array(),
            'email' => array(),
            'password' => array(),
            'data' => array(),
            'name' => array('type'=>'func', 'func'=>function($item){
                return trim(implode(" ", array($item->firstname, $item->lastname)));
            })
        );    
    }

}