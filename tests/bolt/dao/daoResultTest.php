<?php

class daoResultTest extends bolt_test {

    private $i = false;

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'config' => array(
                'autoload' => array()
            ),
            'core' => array('dao', 'bucket')
        ));

        // object
        $this->result = b::dao('daoResultTestClass');

    }

    public function testInit() {
        $this->assertTrue(is_a($this->result, 'daoResultTestClass'));
    }
    public function testGet() {
        $result = $this->result->get();
        $this->assertTrue(is_a($result, '\bolt\dao\result'));
    }


}

class daoResultTestClass extends \bolt\dao\item {

    // struct
    public function getStruct() {
        return array(
            'id' => array(),
            'string' => array('cast' => 'string'),
            'bool' => array('cast' => 'bool'),
            'key1' => array('default' => 'poop'),
            'float' => array('cast' => 'float', 'default' => 'a'),
            'class' => array(
                    'type' => 'dao',
                    'class' => 'daoTestTypeClass',
                    'args' => array('static', '$key1')
                )
        );
    }

    public function get() {
        return $this->result(array(
                array(
                    'id' => 'item1',
                    'string' => 'pooper'
                ),
                array(
                    'id' => 'item2',
                    'bool' => true
                )
            ));
    }

}

