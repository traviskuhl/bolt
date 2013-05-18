<?php


class responseJsonTest extends bolt_test {

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

        $this->r = new \bolt\browser\response\json();

        $this->content = array('1' => 2);
        $this->status = 200;

        $this->r->setContent($this->content);
        $this->r->setStatus($this->status);

    }

    public function testTestContenType() {
        $this->assertEquals(array(
                100 => 'application/json',
                200 => 'application/json;secure'
            ), \bolt\browser\response\json::$contentType);
    }

    public function testGetContent() {
        $r = $this->r->handle();

        $this->assertEquals(json_encode(array(
                'status' => $this->status,
                'response' => $this->content
            )), $r);

        $this->assertEquals('application/json', $this->r->getContentType());

    }

    public function testGetContentSecure() {
        $this->r->setContentType('application/json;secure');

        $r = $this->r->handle();

        $this->assertEquals('while(1);'.json_encode(array(
                'status' => $this->status,
                'response' => $this->content
            )), $r);

        $this->assertEquals('application/json', $this->r->getContentType());

    }

}