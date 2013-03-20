<?php

class eventTest extends bolt_test {

    private $flag = false;

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'core' => array('event')
        ));

        $this->event = new eventTestClass();

    }

    public function testOn() {
        $this->event->on("test", function() {});
        $this->assertTrue(array_key_exists('test', $this->event->getEvents()));
    }

    public function testFire() {
        $this->event->on("test", array($this, '_fireFunc'));
        $this->event->fire("test");
        $this->assertTrue($this->flag);
    }

    public function _fireFunc() {
        $this->flag = true;
    }

}

class eventTestClass extends \bolt\event {


}