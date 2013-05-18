<?php

class controllerTest extends bolt_test {

    private $i = false;

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'mode' => 'browser'
        ));

        $this->tc = new testController();
        $this->tcd = new testControllerWithDefaults();

        // remove request/response instances to
        // reset env
        b::bolt()
            ->removeInstance('request')
            ->removeInstance('response');

    }

    public function testFactory() {
        $this->assertInstanceOf('bolt\browser\controller', b::controller());
    }

    public function testControllerInterface() {
        $this->assertTrue(in_array('bolt\browser\iController', class_implements(b::controller())));
    }

    public function testContructSetLayout() {
        $this->assertFalse($this->tc->hasLayout());
        $this->assertTrue($this->tcd->hasLayout());
    }

    public function testInit() {
        $this->assertTrue($this->tc->initRun);
    }

    public function testGetAccept() {
        $str = 'accept string';
        b::request()->setAccept($str);
        $this->assertEquals($str, $this->tc->getAccept());
    }

    public function testGetStatus() {
        $status = 418;
        b::response()->setStatus($status);
        $this->assertEquals($status, $this->tc->getStatus());
    }

    public function testSetStatus() {
        $status = 418;
        $this->tc->setStatus($status);
        $this->assertEquals($status, b::response()->getStatus());
    }

    public function testRun() {
        $c = new testControlleNoMethod();
        $this->assertEquals('', $c->run());
    }

    public function testBuildDispatch() {
        $c = new testControllerWithDispatch();
        $c->build();
        $this->assertEquals($c->getContent(), 'dispatch');
    }

    public function testBuildStandardMethods() {
        $methods = array('get','post','put','delete','head');
        foreach ($methods as $method) {
            b::request()->setMethod($method);
            $this->tc->build();
            $this->assertEquals($method, $this->tc->getContent());
        }
    }

    public function testBuildUnknownMethod() {
        b::request()->setMethod('unknown');
        $o = new testControlleNoMethod();
        $this->assertEquals($o, $o->build());
    }

    public function testBuildActionMethods() {
        $action = 'Action';
        $methods = array('get','post','put','delete','head');
        b::request()->setAction($action);
        foreach ($methods as $method) {
            b::request()->setMethod($method);
            $this->tc->build();
            $this->assertEquals($method.$action, $this->tc->getContent());
        }
    }

    public function testBuildWithParams() {
        b::request()->setParams(b::bucket(array('param' => 1)));
        $o = new testControllerWithParam();
        $o->build();
        $this->assertEquals(1, $o->getContent());
    }

    public function testBuildWithDefaultParams() {
        b::request()->setMethod('post');
        $o = new testControllerWithParam();
        $o->build();
        $this->assertEquals('default', $o->getContent());
    }

}

class testView extends \bolt\browser\view {
    public function build() {
        $this->setContent('test view');
    }
}

class testController extends \bolt\browser\controller {
    public function init() {
        $this->initRun = true;
    }
    public function get() {
        $this->setContent('get');
    }
    public function post() {
        $this->setContent('post');
    }
    public function put() {
        $this->setContent('put');
    }
    public function delete() {
        $this->setContent('delete');
    }
    public function head() {
        $this->setContent('head');
    }
    public function getAction() {
        $this->setContent('getAction');
    }
    public function postAction() {
        $this->setContent('postAction');
    }
    public function putAction() {
        $this->setContent('putAction');
    }
    public function deleteAction() {
        $this->setContent('deleteAction');
    }
    public function headAction() {
        $this->setContent('headAction');
    }
}


class testControllerWithDefaults extends \bolt\browser\controller {
    public $layout = "file";
    public $templateDir = "folder";
    public function init() {
        $this->initRun = true;
        $this->var = 1;
    }
}

class testControllerWithDispatch extends \bolt\browser\controller {
    public function dispatch() {
        $this->setContent("dispatch");
    }
}

class testControllerWithParam extends \bolt\browser\controller {
    public function get($param) {
        $this->setContent($param);
    }
    public function post($param='default') {
        $this->setContent($param);
    }
}

class testControlleNoMethod extends \bolt\browser\controller {

}