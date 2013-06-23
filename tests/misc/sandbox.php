#!/usr/bin/php
<?php

require(__DIR__."/../../src/bolt.php");

error_reporting(E_ALL^E_STRICT);


b::init(array(

    ));

class testmodel extends \bolt\model\base {

    public function getStruct() {
        return [
            'id' => ['type' => 'unique', 'primary' => true],
            'title' => ['type' => 'string'],
            'data' => ['type' => 'array'],
            'time' => ['type' => 'datetime'],
            'poop' => ['type' => 'string']
        ];
    }


}


$model = b::model('testmodel');

$model->set([
    'title' => 'poop',
    'data' => array('sss'),
    'time' => time(),
    'poop' => 'fine'
]);

var_dump($model->value('poop'));