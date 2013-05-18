<?php


class responsePlainTest extends bolt_test {

    private $i = false;

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'mode' => 'browser'
        ));

        // remove request/response instances to
        // reset env
        b::bolt()
            ->removeInstance('request')
            ->removeInstance('response');

        $this->r = new \bolt\browser\response\plain();

        $this->status = 200;
        $this->text = "this is a test";

        $this->r->setContent($this->text);
        $this->r->setStatus($this->status);

    }

    public function testTestContenType() {
        $this->assertEquals(array('100' => 'text/plain'), \bolt\browser\response\plain::$contentType);
    }

    public function testGetContent() {
        $r = $this->r->handle();

        $this->assertEquals($this->text, $r);

        $this->assertEquals('text/plain', $this->r->getContentType());

    }


}