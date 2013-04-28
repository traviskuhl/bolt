<?php

class requestTest extends bolt_test {

    private $i = false;

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'mode' => 'browser'
        ));

        // reset route instance each time
        b::bolt()->removeInstance('route')->removeInstance('request');

        $_GET = array('getvar' => 1);
        $_POST = array('postvar' => 1);
        $_REQUEST = array_merge($_GET, $_POST);

        $_SERVER['HTTP_HOST'] = "test.bolthq.com";
        $_SERVER['HTTP_ACCEPT'] = 'text/html,other_stuff';
        $_SERVER['PATH_INFO'] = "test/path/here";

        $this->r = new \bolt\browser\request();

    }

    public function testMagicGetGet() {
        $this->assertInstanceOf('\bolt\bucket', $this->r->get);
        $this->assertEquals(1, $this->r->get->getvar->value);
    }
    public function testMagicGetPost() {
        $this->assertInstanceOf('\bolt\bucket', $this->r->post);
        $this->assertEquals(1, $this->r->post->postvar->value);
    }
    public function testMagicGetRequest() {
        $this->assertInstanceOf('\bolt\bucket', $this->r->request);
        $this->assertEquals(1, $this->r->request->getvar->value);
        $this->assertEquals(1, $this->r->request->postvar->value);
    }
    public function testMagicGetHeaders() {
        $this->assertInstanceOf('\bolt\bucket', $this->r->headers);
        $this->assertEquals($_SERVER['HTTP_HOST'], $this->r->headers->getValue('host'));
    }
    public function testMagicGetRouteParam() {
        $this->assertInstanceOf('\bolt\bucket', $this->r->params);
    }
    public function testMagicGetServer() {
        $this->assertInstanceOf('\bolt\bucket', $this->r->server);
        $this->assertEquals($_SERVER['PATH_INFO'], $this->r->server->getValue('path_info'));
    }
    public function testMagicGetInput() {
        $this->assertEquals("", $this->r->input);
    }
    public function testMagicGetDefault() {
        $this->assertFalse($this->r->novar->value);
    }

    public function testMagicSet() {
        $this->assertFalse($this->r->var->value);
        $this->r->var = 1;
        $this->assertEquals(1, $this->r->var->value);
    }

    public function testGetParmasGet() {
        $this->assertInstanceOf('\bolt\bucket', $this->r->getParams('get'));
        $this->assertEquals(1, $this->r->getParams('get')->getvar->value);
    }
    public function testGetParmasPost() {
        $this->assertInstanceOf('\bolt\bucket', $this->r->getParams('post'));
        $this->assertEquals(1, $this->r->getParams('post')->postvar->value);
    }
    public function testGetParmasRequest() {
        $this->assertInstanceOf('\bolt\bucket', $this->r->getParams('request'));
        $this->assertEquals(1, $this->r->getParams('request')->getvar->value);
        $this->assertEquals(1, $this->r->getParams('request')->postvar->value);
    }
    public function testGetParamsDefault() {
        $this->assertInstanceOf('\bolt\bucket', $this->r->getParams());
        $this->assertFalse($this->r->getParams()->var->value);
    }

    public function testSetParams() {
        $b = b::bucket(array('testvar' => 1));
        $this->assertInstanceOf('\bolt\browser\request', $this->r->setParams($b));
        $this->assertEquals(1, $this->r->testvar->value);
    }

    public function testGetHeaders() {
        $this->assertInstanceOf('\bolt\bucket', $this->r->getHeaders());
        $this->assertEquals($_SERVER['HTTP_HOST'], $this->r->getHeaders()->get('host'));
    }

    public function testGetInput() {
        $this->assertEquals("", $this->r->getInput());
    }

    public function testGetSetAction() {
        $this->assertFalse($this->r->getAction());
        $this->assertInstanceOf('\bolt\browser\request', $this->r->setAction('test'));
        $this->assertEquals('test', $this->r->getAction());
    }

    public function testGetMethod() {
        $this->assertEquals('GET', $this->r->getMethod());
    }
    public function testSetMethod() {
        $this->assertEquals('GET', $this->r->getMethod());
        $this->assertInstanceOf('\bolt\browser\request', $this->r->setMethod('POST'));
        $this->assertEquals('POST', $this->r->getMethod());
    }
    public function testGetAccept() {
        $this->assertEquals('text/html', $this->r->getAccept());
    }
    public function testSetAccept() {
        $this->assertEquals('text/html', $this->r->getAccept());
        $this->assertInstanceOf('\bolt\browser\request', $this->r->setAccept('text/xml'));
        $this->assertEquals('text/xml', $this->r->getAccept());
    }

    public function testRunNoMatch() {
        $this->assertFalse($this->r->run());
    }

    public function testRunPassedPath() {
        b::route('test_route', 'reqTestController');
        $this->assertInstanceOf('\bolt\browser\request', $this->r->run('test_route'));
        $this->assertInstanceOf('reqTestController', b::response()->getController());
    }

    public function testRunServerPath() {
        b::route($_SERVER['PATH_INFO'], 'reqTestController');
        $this->assertInstanceOf('\bolt\browser\request', $this->r->run());
        $this->assertInstanceOf('reqTestController', b::response()->getController());
    }

    public function testRunClosure() {
        $test = $this;
        b::route('test_route', function() use ($test){
            $test->assertTrue(true);
            return 'test';
        });
        $this->assertInstanceOf('\bolt\browser\request', $this->r->run('test_route'));
        $this->assertInstanceOf('\bolt\browser\controller', b::response()->getController());
        $this->assertEquals('test', b::response()->getController()->getContent('test'));
    }

    public function testRunClosureWithParams() {
        $test = $this; $param = 'test';
        b::route('test_route/{value}', function($value, $novar=1) use ($test, $param){
            $test->assertEquals($value, $param);
            $test->assertEquals(1, $novar);
        });
        $this->assertInstanceOf('\bolt\browser\request', $this->r->run("test_route/{$param}"));
        $this->assertInstanceOf('\bolt\browser\controller', b::response()->getController());
    }

    public function testRunView() {
        b::route('test_route', 'reqTestView');
        $this->assertInstanceOf('\bolt\browser\request', $this->r->run('test_route'));
        $this->assertEquals('test', b::response()->getController()->getContent('test'));
    }

    public function testRunControllerPassthrough() {
        b::route('test_route', 'testControllerPassthrough');
        $this->assertInstanceOf('\bolt\browser\request', $this->r->run('test_route'));
        $this->assertInstanceOf('reqTestController', b::response()->getController());
    }

    public function testRunControllerPassthroughView() {
        b::route('test_route', 'testControllerPassthroughView');
        $this->assertInstanceOf('\bolt\browser\request', $this->r->run('test_route'));
        $this->assertEquals('test', b::response()->getController()->getContent('test'));
    }

}

class reqTestController extends \bolt\browser\controller {
    public function init() {

    }
}

class reqTestView extends \bolt\browser\view {
    public function build() {
        $this->setContent('test');
    }
}


class testControllerPassthrough extends \bolt\browser\controller {
    public function run() {
        return new \reqTestController();
    }
}

class testControllerPassthroughView extends \bolt\browser\controller {
    public function run() {
        return new \reqTestView();
    }
}
