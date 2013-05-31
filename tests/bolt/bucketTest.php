<?php

class bucketTest extends bolt_test {

    private $i = false;

    public function setUp() {

    }

    private function factory() {
        return call_user_func(array('\bolt\bucket', 'factory'), func_get_args());
    }
    private function bytype() {
        return call_user_func_array(array('\bolt\bucket', 'bytype'), func_get_args());
    }


    /// tests
    public function testFactoryNoArgs() {
        $this->assertInstanceOf('\bolt\bucket\bArray', $this->factory());
    }

    public function testFactoryString() {
        $this->assertInstanceOf('bolt\bucket\bString', $this->factory('string', false));
    }
    public function testFactoryObject() {
        $this->assertInstanceOf('bolt\bucket\bObject', $this->factory(new StdClass));
    }
    public function testFactoryArray() {
        $this->assertInstanceOf('bolt\bucket\bArray', $this->factory(array(9)));
    }

    public function testByTypeBucket() {
        $o = $this->factory('o');
        $this->assertEquals($o->bGuid(), $this->bytype($o)->bGuid());
    }

    public function testByTypeBool() {
        $this->assertInstanceOf('bolt\bucket\bString', $this->bytype(true));
    }
    public function testByTypeInt() {
        $this->assertInstanceOf('bolt\bucket\bString', $this->bytype(1));
    }
    public function testByTypeDoubleFloat() {
        $this->assertInstanceOf('bolt\bucket\bString', $this->bytype(1.234));
    }
    public function testByTypeNull() {
        $this->assertInstanceOf('bolt\bucket\bString', $this->bytype(null));
    }
    public function testByTypeString() {
        $this->assertInstanceOf('bolt\bucket\bString', $this->bytype('string'));
    }

}