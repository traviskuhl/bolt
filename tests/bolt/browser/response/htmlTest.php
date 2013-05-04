<?php


class responseHtmlTest extends bolt_test {

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

        $this->r = new \bolt\browser\response\html();
        $this->c = new \bolt\browser\controller();

        $this->status = 200;
        $this->html = "this is a test";

        $this->c->setContent($this->html);
        $this->c->setStatus($this->status);

    }

    public function testTestContenType() {
        $this->assertEquals(array('100' => 'text/html'), \bolt\browser\response\html::$contentType);
    }

    public function testGetContent() {
        $r = $this->r->getContent($this->c);

        $this->assertEquals($this->html, $r);

        $this->assertEquals('text/html', b::response()->getContentType());

    }

}