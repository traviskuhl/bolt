<?php

use bolt\browser\route\parser;

class routParserTest extends bolt_test {

    private $i = false;

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'mode' => 'browser'
        ));

        // reset route instance each time
        b::bolt()->removeInstance('route');

        $this->r = new routeParserTestParser('test', 'routeParserTestController');

    }

    public function testMagicCallGetDefault() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->nope());
    }

    public function testMagicCallGetPath() {
        $this->assertEquals('test', $this->r->getPath());
    }
    public function testMagicCallGetController() {
        $this->assertEquals('routeParserTestController', $this->r->getController());
    }

    public function testMagicCallSetPath() {
        $this->r->setPath('test2');
        $this->assertEquals("test2", $this->r->getPath());
    }
    public function testMagicCallSetController() {
        $this->r->setController('test2');
        $this->assertEquals("test2", $this->r->getController());
    }

    public function testValidateString() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->validate('test', 'xx'));
        $v = $this->r->getValidators();
        $this->assertEquals($v['test'], 'xx');
    }
    public function testValidateArray() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->validate(array('test' => 'xx', 'test2' => 'xx2') ));
        $v = $this->r->getValidators();
        $this->assertEquals($v['test'], 'xx');
        $this->assertEquals($v['test2'], 'xx2');
    }
    public function testGetValidator() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->validate('test', 'xx'));
        $this->assertEquals('xx', $this->r->getValidator('test'));
    }
    public function testGetValidatorDefault() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->validate('test', 'xx'));
        $this->assertEquals('xx2', $this->r->getValidator('test1', 'xx2'));
    }
    public function testName() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->name('test'));
        $this->assertEquals('test', $this->r->getName());
    }
    public function testMethodSingleString() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->method('GET'));
        $this->assertEquals(array('GET'), $this->r->getMethod());
    }
    public function testMethodCSVString() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->method('GET,POST,HEAD'));
        $this->assertEquals(array('GET','POST','HEAD'), $this->r->getMethod());
    }
    public function testMethodArray() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->method(array('GET','POST','HEAD')));
        $this->assertEquals(array('GET','POST','HEAD'), $this->r->getMethod());
    }
    public function testAction() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->action('test'));
        $this->assertEquals('test', $this->r->getAction());
    }
    public function testDaoSingle() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->dao('test', 'class'));
        $daos = $this->r->getDaos();
        $this->assertEquals(array('class' => 'class', 'args' => false),$daos['test']);
    }
    public function testDaoArray() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->dao(array(array('test', 'class'), array('test1', 'class1'))));
        $daos = $this->r->getDaos();
        $this->assertEquals(array('class' => 'class', 'args' => false),$daos['test']);
        $this->assertEquals(array('class' => 'class1', 'args' => false),$daos['test1']);
    }
    public function testDaoSingleWithArgs() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->dao('test', 'class', array('test'=>'x')));
        $daos = $this->r->getDaos();
        $this->assertEquals(array('class' => 'class', 'args' => array('test'=>'x')),$daos['test']);
    }

    public function testBefore() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->before(function(){}));
        $e = $this->r->getEvents();
        $this->assertEquals(2, count($e['before'])); // 2 because the parser classes adds one if it's own
    }

    public function testAfter() {
        $this->assertInstanceOf('routeParserTestParser', $this->r->after(function(){}));
        $e = $this->r->getEvents();
        $this->assertEquals(1, count($e['after']));
    }


}

class routeParserTestParser extends \bolt\browser\route\parser {

    public function match($path) {
        return ($path == $this->getPath());
    }

}

class routeParserTestController extends \bolt\browser\controller {

}