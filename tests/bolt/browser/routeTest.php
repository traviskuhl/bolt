<?php

use bolt\browser\route\parser;


class routTest extends bolt_test {

    private $i = false;

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'mode' => 'browser'
        ));

        // reset route instance each time
        b::bolt()->removeInstance('route');

        $this->r = new \bolt\browser\route();
        $this->r->setDefaultParser('routeTestParser');

    }

    public function testDefaultSelf() {
        $this->assertEquals($this->r, $this->r->_default());
    }
    public function testDefaultRegister() {
        $this->assertInstanceOf('routeTestParser', $this->r->_default('a', 'b'));
    }

    public function testGetSetDefaultParser() {
        $this->assertEquals('routeTestParser', $this->r->getDefaultParser());
        $this->assertInstanceOf('\bolt\browser\route', $this->r->setDefaultParser('x'));
        $this->assertEquals('x', $this->r->getDefaultParser());
    }

    public function testGetRoutes() {
        $this->assertEquals(array(), $this->r->getRoutes());
    }

    public function testGetRouteByName() {
        $this->assertInstanceOf('routeTestParser', $this->r->register('a', 'routeTestController')->name('test'));
        $this->assertInstanceOf('routeTestParser', $this->r->getRouteByName('test'));
    }
    public function testGetRouteByNameFail() {
        $this->assertFalse($this->r->getRouteByName('test'));
    }
    public function testRegisterClass() {
        $this->assertInstanceOf('routeTestParser', $this->r->register('a', 'routeTestController'));
        $routes = $this->r->getRoutes();
        $this->assertEquals(1, count($routes));
        $this->assertInstanceOf('routeTestParser', $routes[0]);
    }
    public function testRegisterObject() {
        $o = new routeTestParser("a", 'routeTestController');
        $this->r->register($o);
        $routes = $this->r->getRoutes();
        $this->assertEquals(1, count($routes));
        $this->assertInstanceOf('routeTestParser', $routes[0]);
    }
    public function testRegisterObjectBad() {
        $o = new routeTestController();
        $this->assertFalse($this->r->register($o));
        $this->assertEquals(0, count($this->r->getRoutes()));
    }

    public function testMatchPath() {
        $this->r->register('a', 'routeTestController');
        $m = $this->r->match('a');
        $this->assertInstanceOf('routeTestParser', $m);
        $this->assertEquals('routeTestController', $m->getController());
    }

    public function testMatchPathMethod() {
        $this->r->register('a', 'routeTestController')->method('GET');
        $m = $this->r->match('a', 'GET');
        $this->assertInstanceOf('routeTestParser', $m);
        $this->assertEquals('routeTestController', $m->getController());
    }

    public function testMatchPathMethodNoMatch() {
        $this->r->register('a', 'routeTestController')->method('GET');
        $m = $this->r->match('a', 'POST');
        $this->assertFalse($m);
    }


    public function testMatchNoRoute() {
        $this->assertFalse($this->r->match('a'));
    }

    public function testMatchFallbackStar() {
        $this->r->register('*', 'routeTestController')->method('GET');
        $m = $this->r->match('a', 'GET');
        $this->assertInstanceOf('routeTestParser', $m);
        $this->assertEquals('routeTestController', $m->getController());
    }

    public function testUrlByName() {
        $this->r->register('test/test', 'routeTestController')->name('test');
        $this->assertEquals('http://test.bolthq.com/test/test', $this->r->url('test'));
    }
    public function testUrlByNameWithData() {
        $this->r->register('test/{name}', 'routeTestController')->name('test');
        $this->assertEquals('http://test.bolthq.com/test/test', $this->r->url('test', array('name' => 'test')));
    }
    public function testUrlByNameWithDataQuery() {
        $this->r->register('test/{name}', 'routeTestController')->name('test');
        $this->assertEquals('http://test.bolthq.com/test/test?travis=awesome', $this->r->url('test', array('name' => 'test'), array('travis' => 'awesome')));
    }
    public function testUrlByNameWithDataQueryArgs() {
        $this->r->register('test/{name}', 'routeTestController')->name('test');
        $args = array(
            'port' => 9,
            'host' => 'test2.bolthq.com',
            'user' => 'test',
            'pass' => 'test',
            'scheme' => 'https',
            'fragment' => 'coco'
        );
        $this->assertEquals('https://test:test@test2.bolthq.com:9/test/test?travis=awesome#coco', $this->r->url('test', array('name' => 'test'), array('travis' => 'awesome'), $args));
    }
    public function testUrlWithUrl() {
        $url = "http://test2.bolthq.com/test";
        $this->assertEquals($url, $this->r->url($url));
    }
    public function testUrlWithUrlParams() {
        $url = "http://test2.bolthq.com/test";
        $this->assertEquals($url.'?test=test', $this->r->url($url, array('test'=>'test')));
    }

    public function testloadClassRoutes() {
        $this->assertInstanceOf('\bolt\browser\route', $this->r->loadClassRoutes());
        $r = $this->r->getRoutes();
        $this->assertEquals(3, count($r));
        $this->assertFalse($this->r->getRouteByName('t4'));
    }

    public function testloadClassRoutesStaticVariable() {
        $this->assertInstanceOf('\bolt\browser\route', $this->r->loadClassRoutes());
        $this->assertInstanceOf('routeTestParser', $this->r->getRouteByName('t1'));
    }
    public function testloadClassRoutesStaticFunc() {
        $this->assertInstanceOf('\bolt\browser\route', $this->r->loadClassRoutes());
        $this->assertInstanceOf('routeTestParser', $this->r->getRouteByName('t2'));
        $this->assertInstanceOf('routeTestParser', $this->r->getRouteByName('t3'));
    }

}

class routeTestParser extends \bolt\browser\route\parser {

    public function match($path) {
        return ($path == $this->getPath());
    }

}

class routeTestController extends \bolt\browser\controller\request {
    public static $routes = array(
            array('route' => 'test1', 'name' => 't1')
        );
}

class routeTestController2 extends \bolt\browser\controller\request {
    public static function getRoutes(){
        return array(
            array('route' => 'test2', 'name' => 't2'),
            array('route' => 'test3', 'name' => 't3')
        );
    }
}

class routeTestController3 {
    public static function getRoutes(){
        return array(
            array('route' => 'i don\'t exist', 'name' => 't4')
        );
    }
}