<?php

class daoItemTest extends bolt_test {

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
        $this->item = b::dao('daoTestClass');

    }

    public function testInst() {
        $this->assertTrue(is_a($this->item, 'daoTestClass'));
    }
    public function testValue() {
        $this->assertEquals($this->item->value('test'), 'test');
    }
    public function testGetFunction() {
        $this->assertEquals($this->item->test, 'test');
        $this->assertEquals($this->item->getTest(), 'test');
    }
    public function testTraitGetFunction() {
        $this->assertEquals($this->item->traitTest, 'testt');
        $this->assertEquals($this->item->getTraitTest(), 'testt');
    }
    public function testGetTypeClass() {
        $this->assertEquals($this->item->class, array('static', 'poop'));
    }
    public function testGetCast() {
        $fl = 'a'; settype($fl, 'float');
        $this->assertEquals($this->item->float->value, $fl);
    }

    /// traits
    public function testAddTrait() {
        $this->item->addTrait('\daoTestTraitClass2');
        $this->asserTrue(in_array('\daoTestTraitClass2', $this->item->getTraits()));

    }



}



class daoTestClass extends \bolt\dao\item {

    // traits
    public $traits = array('daoTestTraitClass');

    // struct
    public function getStruct() {
        return array(
            'uid' => array('type' => "uid"),
            'id' => array('type' => "uuid", 'cast' => 'string'),
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

    public function getTest() {
        return 'test';
    }

}

class daoTestTraitClass {

    public function getTraitTest() {
        return 'testt';
    }

}

class daoTestTraitClass2 {

    public function getTraitTest() {
        return 'testt22';
    }

}

class daoTestTypeClass {

    public function get($param1, $param2) {
        return array($param1, (string)$param2);
    }

}