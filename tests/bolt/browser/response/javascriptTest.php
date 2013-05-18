<?php


class responseJavascriptTest extends bolt_test {

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

        $this->r = new \bolt\browser\response\javascript();

        $this->status = 200;
        $this->html = "this is a test";

        $this->r->setContent($this->html);
        $this->r->setStatus($this->status);

    }

    public function testTestContenType() {
        $this->assertEquals(array('100' => 'text/javascript'), \bolt\browser\response\javascript::$contentType);
    }

    public function testGetContent() {
        $this->assertEquals($this->html, $this->r->handle());
        $this->assertEquals('text/javascript', $this->r->getContentType());

    }

}