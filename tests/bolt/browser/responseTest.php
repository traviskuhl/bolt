<?php

class responseTest extends bolt_test {

    private $i = false;

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'mode' => 'browser'
        ));

        // reset route instance each time
        b::bolt()->removeInstance('route')->removeInstance('request');


        $this->r = new \bolt\browser\response();

    }

    public function testMagicGetStatus() {
        $this->assertEquals(200, $this->r->status);
    }
    public function testMagicGetContentType() {
        $this->assertEquals(false, $this->r->contentType);
    }
    public function testMagicGetController(){
        $this->assertFalse($this->r->controller);
    }
    public function testMagicGetHeaders() {
        $this->assertInstanceOf('\bolt\bucket', $this->r->headers);
    }
    public function  testMagicGetEmpty() {
        $this->assertFalse($this->r->none);
    }

    public function testMagicSetStatus() {
        $this->assertEquals(418, ($this->r->status = 418));
        $this->assertEquals(418, $this->r->status);
    }
    public function testMagicSetContentType() {
        $this->assertEquals("test", ($this->r->contentType = 'test'));
        $this->assertEquals("test", $this->r->contentType);
    }

    public function testGetSetContentType() {
        $this->assertEquals(false, $this->r->getContentType());
        $this->assertInstanceOf('\bolt\browser\response', $this->r->setContentType('test'));
        $this->assertEquals('test', $this->r->getContentType());
    }

    public function testGetHeaders() {
        $this->assertInstanceOf('\bolt\bucket', $this->r->getHeaders());
    }

    public function testGetSetStatus() {
        $this->assertEquals(200, $this->r->getStatus());
        $this->assertInstanceOf('\bolt\browser\response', $this->r->setStatus(418));
        $this->assertEquals(418, $this->r->getStatus());
    }

    public function testGetSetStatusBad() {
        $this->assertEquals(200, $this->r->getStatus());
        $this->assertInstanceOf('\bolt\browser\response', $this->r->setStatus('no_an_int'));
        $this->assertEquals(500, $this->r->getStatus());
    }


    public function testGetSetController() {
        $c = new respTestController();
        $this->assertFalse($this->r->getController());
        $this->assertInstanceOf('\bolt\browser\response', $this->r->setController($c));
        $this->assertInstanceOf('respTestController', $this->r->getController());
    }

    public function testSetControllerFail() {
        $this->assertFalse($this->r->setController('no_a_controller'));
    }

    public function testGetOutputHandler() {
        $this->r->plug('test', 'respTestOutputHandler');
        $this->r->plug('test2', 'respTestOutputHandler2');
        $this->r->setContentType('test');
        $this->assertInstanceOf('respTestOutputHandler', $this->r->getOutputHandler());
    }

    public function testRun() {
        $c = new respTestController();
        $this->r->plug('test', 'respTestOutputHandler');
        $this->r->setContentType('test')->setController($c);
        $this->assertEquals('test', $this->r->run());
    }

}

class respTestController extends \bolt\browser\controller {
    public function init() {
        $this->setContent('test');
    }
}

class respTestOutputHandler extends \bolt\plugin\factory {
    public static $contentType = array(
        100 => 'test',
    );
    public function getContent($c) {
        return $c->getContent();
    }
}

class respTestOutputHandler2 extends \bolt\plugin\factory {
    public static $contentType = array(
        1 => 'test',
    );
    public function getContent($c) {

    }
}