<?php

use bolt\browser\route\parser;


class viewTest extends bolt_test {

    private $i = false;

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'mode' => 'browser'
        ));

        $this->v = new viewTestView(array('test' => 1));
        $this->v->setController(new viewTestController);

    }

    public function testFactoryDefault() {
        $v = b::view();
        $this->assertInstanceOf('\bolt\browser\view', $v);
    }

    public function testFactoryWithClass() {
        $v = b::view('viewTestView');
        $this->assertInstanceOf('viewTestView', $v);
    }

    public function testImplements() {
        $v = new \bolt\browser\view;
        $this->assertTrue(in_array('bolt\browser\iView', class_implements($v)));
    }

    public function testMagicGet() {
        $this->assertEquals(1, $this->v->test->value);
        $this->assertFalse($this->v->nope->value);
    }
    public function testMagicSet() {
        $this->assertFalse($this->v->test1->value);
        $this->v->test1 = 1;
        $this->assertEquals(1, $this->v->test1->value);
        $this->assertFalse($this->v->test2->value);
    }
    public function testGetParamsParams() {
        $this->assertInstanceOf('bolt\bucket', $this->v->params);
    }
    public function testGetParamsController() {
        $this->assertEquals(1, $this->v->testC1->value);
    }
    public function testGetParamsParam() {
        $this->assertEquals(1, $this->v->test->value);
        $this->assertFalse($this->v->test2->value);
    }
    public function testGetParams() {
        $this->assertInstanceOf('bolt\bucket', $this->v->getParams());
        $a = $this->v->getParams()->asArray();
        $this->assertEquals(array('test'=>1), $a);
    }
    public function testSetParamsBucket() {
        $v = new viewTestView();
        $b = b::bucket();
        $v->setParams($b);
        $this->assertInstanceOf('\bolt\bucket', $v->getParams());
        $v->setParams($b);
        $this->assertEquals($v->getParams()->getGuid(), $b->getGuid());
    }

    public function testGetGuid() {
        $this->assertTrue(is_string($this->v->getGuid()));
    }

    public function testGetController() {
        $this->assertInstanceOf('viewTestController', $this->v->getController());
    }

    public function testSetController() {
        $c = new viewTestController();
        $this->assertFalse( $this->v->getController()->getGuid() == $c->getGuid() );
        $this->v->setController($c);
        $this->assertEquals($c->getGuid(), $this->v->getController()->getGuid());
    }

    public function testGetSetContent() {
        $this->assertFalse($this->v->getContent());
        $this->v->setContent('test');
        $this->assertEquals('test', $this->v->getContent());
    }

    public function testGetSetFile() {
        $this->assertFalse($this->v->getFile());
        $this->v->setFile('test');
        $this->assertEquals('test', $this->v->getFile());
    }

    public function testGetSetRenderer() {
        $this->assertEquals('handlebars', $this->v->getRenderer());
        $this->v->setRenderer('test');
        $this->assertEquals('test', $this->v->getRenderer());
    }

    public function testInit() {
        $v = new viewTestViewInit();
        $this->assertEquals('init', $v->getContent());
    }

    public function testRenderEmpty() {
        $this->v->setContent('test');
        $this->assertEquals('test', $this->v->render());
    }

    public function testRenderBuild() {
        $v = new viewTestViewBuild();
        $v->setRenderer(false);
        $this->assertEquals('build', $v->render());
    }

    public function testRenderBefore() {
        $v = new viewTestViewBefore();
        $v->setRenderer(false);
        $this->assertEquals('before', $v->render());
    }

    public function testRenderAfter() {
        $v = new viewTestViewAfter();
        $v->setRenderer(false);
        $this->assertEquals('after', $v->render());
    }

}


class viewTestController extends \bolt\browser\controller {
    public function init() {
        $this->testC1 = 1;
    }
}

class viewTestView extends \bolt\browser\view {

}


class viewTestViewInit extends \bolt\browser\view {
    public function init() {
        $this->setContent('init');
    }
}

class viewTestViewBuild extends \bolt\browser\view {
    public function build() {
        $this->setContent('build');
    }
}

class viewTestViewBefore extends \bolt\browser\view {
    public function before() {
        $this->setContent('before');
    }
}

class viewTestViewAfter extends \bolt\browser\view {
    public function after() {
        $this->setContent('after');
    }
}