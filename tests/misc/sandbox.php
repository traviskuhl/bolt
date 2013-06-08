#!/usr/bin/php
<?php

require(__DIR__."/../../src/bolt.php");

error_reporting(E_ALL^E_STRICT);


b::init(array(

    ));

class testmodel extends \bolt\model\base {

    public function getStruct() {
        return array(
            'id' => array('type' => 'unique'),
            'name' => array()
        );
    }

    public function find() {


    }


}