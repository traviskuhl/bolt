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
        $this->assertTrue($this->tc->initRun->value);
    }

    public function testGetGuid() {
        $this->assertTrue((strlen($this->tc->getGuid()) > 0));
    }

    public function testMagicSet() {
        $this->tc->var = 1;
        $this->assertEquals($this->tc->getParam('var')->value, 1);
    }

    public function testMagicGetRequest() {
        $this->assertInstanceOf('bolt\browser\request', $this->tc->request);
    }

    public function testMagicGetResponse() {
        $this->assertInstanceOf('bolt\browser\response', $this->tc->response);
    }

    public function testMagicGetParam() {
        $this->tc->var = 1;
        $this->assertEquals($this->tc->var->value, 1);
    }

    public function testSetContentString() {
        $str = "this is a string";
        $this->tc->setContent($str);
        $this->assertEquals($this->tc->getContent(), $str);
    }

    public function testSetContentView() {
        $str = "this is a string";
        $view = new \bolt\browser\view();
        $view->setContent($str);
        $this->tc->setContent($view);
        $this->assertEquals($this->tc->getContent(), $str);
    }

    public function testGetContent() {
        $this->assertFalse($this->tc->getContent());
        $str = "this is a string";
        $this->tc->setContent($str);
        $this->assertEquals($this->tc->getContent(), $str);
    }

    public function testGetTemplateDir() {
        $this->assertFalse($this->tc->getTemplateDir());
        $this->assertEquals($this->tcd->getTemplateDir(), 'folder');
    }

    public function testGetParams() {
        $this->assertInstanceOf('\bolt\bucket', $this->tc->getParams());
    }

    public function testGetParam() {
        $this->assertFalse($this->tc->getParam('novar')->value, false);
        $this->assertEquals($this->tcd->getParam('var')->value, 1);
        $this->tc->var = 1;
        $this->assertEquals($this->tc->getParam('var')->value, 1);
    }

    public function testGetParamValue() {
        $this->assertFalse($this->tc->getParamValue('novar'), false);
        $this->assertEquals($this->tcd->getParamValue('var'), 1);
        $this->tc->var = 1;
        $this->assertEquals($this->tc->getParamValue('var'), 1);
    }

    public function testSetLayoutString() {
        $this->assertFalse($this->tc->getLayout());
        $this->tc->setLayout('file');
        $this->assertInstanceOf('bolt\browser\view', $this->tc->getLayout());
    }

    public function testSetLayoutView() {
        $view = new \bolt\browser\view();
        $guid = $view->getGuid();
        $this->tc->setLayout($view);
        $this->assertInstanceOf('bolt\browser\view', $this->tc->getLayout());
        $this->assertEquals($guid, $this->tc->getLayout()->getGuid());
    }

    public function testHasLayout() {
        $this->assertFalse($this->tc->hasLayout());
        $this->assertTrue($this->tcd->hasLayout());
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

    public function testRunDispatch() {
        $c = new testControllerWithDispatch();
        $c->run();
        $this->assertEquals($c->getContent(), 'dispatch');
    }

    public function testRunStandardMethods() {
        $methods = array('get','post','put','delete','head');
        foreach ($methods as $method) {
            b::request()->setMethod($method);
            $this->tc->run();
            $this->assertEquals($method, $this->tc->getContent());
        }
    }

    public function testRunUnknownMethod() {
        b::request()->setMethod('unknown');
        $o = new testControlleNoMethod();
        $this->assertEquals($o, $o->run());
    }

    public function testRunActionMethods() {
        $action = 'Action';
        $methods = array('get','post','put','delete','head');
        b::request()->setAction($action);
        foreach ($methods as $method) {
            b::request()->setMethod($method);
            $this->tc->run();
            $this->assertEquals($method.$action, $this->tc->getContent());
        }
    }

    public function testRunWithParams() {
        b::request()->setParams(b::bucket(array('param' => 1)));
        $o = new testControllerWithParam();
        $o->run();
        $this->assertEquals(1, $o->getContent());
    }

    public function testRunWithDefaultParams() {
        b::request()->setMethod('post');
        $o = new testControllerWithParam();
        $o->run();
        $this->assertEquals('default', $o->getContent());
    }

    public function testRenderTemplate() {
        $tmpl = INC."/test.template.php";
        $vars = array();
        $this->tc->renderTemplate($tmpl, $vars);
        $this->assertEquals("test template", $this->tc->getContent());
    }

    public function testRenderString() {
        $str = 'test string';
        $vars = array();
        $this->tc->renderString($str, $vars);
        $this->assertEquals($str, $this->tc->getContent());
    }

    public function testRenderStringView() {
        $this->assertEquals('test view', $this->tc->render('testView'));
    }

    public function testRenderView() {
        $this->assertEquals('test view', $this->tc->render(new testView()));
    }

    public function testRenderNonView() {
        $this->assertEquals(false, $this->tc->render('unknown class'));
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