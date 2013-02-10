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
        $this->result = \bolt\dao\result::create(
            'daoResultTestClass',
            array(
                array(
                    'id' => 'item1',
                    'string' => 'pooper'
                ),
                array(
                    'id' => 'item2',
                    'bool' => true
                )
            ),
            'id'
        );

    }

    public function testInit() {
        $this->assertTrue(is_a($this->result, '\bolt\dao\result'));
    }
    public function testLoaded() {
        $this->assertTrue($this->result->loaded());
    }
    public function testMetaSetAndGet() {
        $item = new daoResultTestClass(array('test'=>'poop'));
        $this->result->setMeta($item);
        $this->assertTrue(is_a($this->result->getMeta(), 'daoResultTestClass'));
    }
    public function testMetaMagicGet() {
        $item = new daoResultTestClass(array('test'=>'poop'));
        $this->result->setMeta($item);
        $this->assertEquals($this->result->test, 'poop');
    }
    public function testMetaMagicSet() {
        $item = new daoResultTestClass(array('test'=>'poop'));
        $this->result->setMeta($item);
        $this->result->poop = 'test';
        $this->assertEquals($this->result->poop, 'test');
    }
    public function testGetSetTotal() {
        $t = 10;
        $this->assertTrue(is_a($this->result->setTotal($t), '\bolt\dao\result'));
        $this->assertEquals($t, $this->result->getTotal());
    }
    public function testGetSetLimit() {
        $t = 10;
        $this->assertTrue(is_a($this->result->setLimit($t), '\bolt\dao\result'));
        $this->assertEquals($t, $this->result->getLimit());
    }
    public function testGetSetOffset() {
        $t = 10;
        $this->assertTrue(is_a($this->result->setOffset($t), '\bolt\dao\result'));
        $this->assertEquals($t, $this->result->getOffset());
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

}

