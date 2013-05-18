<?php


class responseHandlerTest extends bolt_test {

    private $i = false;

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'mode' => 'browser'
        ));

        $this->h = new testHandler();

    }

    public function testDefaultHandle() {
        $this->assertFalse($this->h->handle());
    }

    public function testGetSetContentType() {
        $this->assertFalse($this->h->getContentType());
        $this->assertEquals($this->h, $this->h->setContentType('test'));
        $this->assertEquals('test', $this->h->getContentType());
    }

    public function testGetSetContent() {
        $this->assertFalse($this->h->getContent());
        $this->assertEquals($this->h, $this->h->setContent('test'));
        $this->assertEquals('test', $this->h->getContent());
    }


    public function testGetSetData() {
        $this->assertEquals(array(), $this->h->getData());
        $this->assertEquals($this->h, $this->h->setData(array('test')));
        $this->assertEquals(array('test'), $this->h->getData());
    }

    public function testGetSetStatus() {
        $this->assertEquals(0, $this->h->getStatus());
        $this->assertEquals($this->h, $this->h->setStatus(200));
        $this->assertEquals(200, $this->h->getStatus());
    }

}

class testHandler extends \bolt\browser\response\handler {


}