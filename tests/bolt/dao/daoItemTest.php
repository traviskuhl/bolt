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
        $this->item = b::dao('daoItemTestClass');

    }

    public function testPlaceholder() {
        $this->assertTrue(true);
    }

    // // general
    // public function testLoaded() {
    //     $this->assertFalse($this->item->loaded());
    //     $this->item->poop = true;
    //     $this->assertTrue($this->item->loaded());
    // }

    // // getters
    // public function testInst() {
    //     $this->assertTrue(is_a($this->item, 'daoItemTestClass'));
    // }
    // public function testGetValue() {
    //     $this->assertEquals($this->item->getValue('test'), 'test');
    // }
    // public function testGetFunction() {
    //     $this->assertEquals($this->item->test, 'test');
    //     $this->assertEquals($this->item->getTest(), 'test');
    // }
    // public function testTraitGetFunction() {
    //     $this->assertEquals($this->item->traitTest, 'testt');
    //     $this->assertEquals($this->item->getTraitTest(), 'testt');
    // }
    // public function testGetTypeClass() {
    //     $this->assertEquals($this->item->class->asArray(), array('static', 'poop'));
    // }
    // public function testGetCast() {
    //     $fl = 'a'; settype($fl, 'float');
    //     $this->assertEquals($this->item->float->value, $fl);
    // }

    // /// setters
    // public function testSetValue() {
    //     $this->item->setValue('string', true);
    //     $this->assertEquals("", $this->item->string);
    // }
    // public function testSet() {
    //     $this->item->set(array('string' => true, 'bool' => true));
    //     $this->assertEquals("", $this->item->string->value);
    //     $this->assertTrue($this->item->bool->value);
    // }
    // public function testMagicSet() {
    //     $this->item->string = true;
    //     $this->assertEquals("", $this->item->string);
    // }

    // /// traits
    // public function testConstructTrait() {
    //     $this->assertTrue(array_key_exists('gettraittest', $this->item->getTraits()));
    //     $this->assertTrue(array_key_exists('daoTestTraitClass', $this->item->getTraitInstances()));
    // }
    // public function testAddTrait() {
    //     $this->item->addTrait('\daoTestTraitClass2');
    //     $this->assertTrue(array_key_exists('gettraittest2', $this->item->getTraits()));
    //     $this->assertTrue(array_key_exists('\daoTestTraitClass2', $this->item->getTraitInstances()));
    // }
    // public function testCallTraitClass() {
    //     $this->assertEquals('testt', $this->item->callTrait('getTraitTest'));
    //     $this->assertEquals('testt', $this->item->callTrait('gettraittest'));
    // }
    // public function testCallTraitClosure() {
    //     $this->item->addTrait(
    //         'getClosureTrait',
    //         function(){
    //             return func_get_args();
    //         },
    //         array('static', '$key1')
    //     );
    //     $this->assertEquals(array('static','poop'), $this->item->callTrait('getClosureTrait'));
    //     $this->assertEquals(array('static','poop'), $this->item->callTrait('getclosuretrait'));
    // }

    // // normalize
    // public function testNormalize() {
    //     $expect = array(
    //         'string' => "",
    //         'bool' => false,
    //         'key1' => 'poop',
    //         'class' => array('static', 'poop')
    //     );
    //     $actual = $this->item->normalize();
    //     foreach ($expect as $key => $value) {
    //         $this->assertEquals($value, $actual[$key]);
    //     }
    // }
    // public function testNormalizeTraitClosure() {
    //     $this->item->addTrait(
    //         'normalizeKey1',
    //         function($param) {
    //             return 'normalized';
    //         },
    //         array('$key1')
    //     );
    //     $expect = array(
    //         'string' => "",
    //         'bool' => false,
    //         'key1' => 'normalized',
    //         'class' => array('static', 'poop')
    //     );
    //     $actual = $this->item->normalize();
    //     foreach ($expect as $key => $value) {
    //         $this->assertEquals($value, $actual[$key]);
    //     }
    // }

}



class daoItemTestClass extends \bolt\dao\item {

    // traits
    public $traits = array('daoTestTraitClass');

    // struct
    public function getStruct() {
        return array(
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

    public function getString() {
        return "";
    }

    public function setString($value) {
        return (string)$value;
    }

    public function getTest() {
        return 'test';
    }

    public function setTest() {
        return 'test';
    }

}

class daoTestTraitClass {

    public function getTraitTest() {
        return 'testt';
    }

}

class daoTestTraitClass2 {

    public function getTraitTest2() {
        return 'testt22';
    }

}

class daoTestTypeClass {

    public function find($param1, $param2) {
        return array($param1, (string)$param2);
    }

}