<?php

class routeTest extends bolt_test {

    private $i = false;

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'config' => array(
                'autoload' => array()
            ),
            'core' => array('config','route')
        ));

    }

    // test register
    public function testRegisterSingle() {

        // register
        $this->assertTrue(b::route('test', '\routeTestClass', 'test'));

        // check if the route is there
        $this->assertTrue(array_key_exists('test', b::route()->getRoutes()));

    }

    // test register
    public function testRegisterMulti() {

        $r = array(
            'test',
            'test/1'
        );

        // register
        $this->assertTrue(b::route($r, '\routeTestClass'));

        $rt = b::route()->getRoutes();

        // check if the route is there
        $this->assertTrue(array_key_exists('test', $rt));
        $this->assertTrue(array_key_exists('test/1', $rt));

    }

}

class routeTestClass {

}