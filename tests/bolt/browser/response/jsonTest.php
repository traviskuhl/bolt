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
        $this->c = new \bolt\browser\controller();

        $this->content = array('1' => 2);
        $this->status = 200;

        $this->c->setContent($this->content);
        $this->c->setStatus($this->status);

    }

    public function testTestContenType() {
        $this->assertEquals(array(
                100 => 'text/javascript',
                200 => 'text/javascript;secure'
            ), \bolt\browser\response\json::$contentType);
    }

    public function testGetContent() {
        // $r = $this->r->getContent($this->c);

        // $this->assertEquals(json_encode(array(
        //         'status' => $this->status,
        //         'response' => $this->content
        //     )), $r);

        // $this->assertEquals('text/javascript', b::response()->getContentType());

    }

    public function testGetContentSecure() {
        $this->c->setContentType('text/javascript;secure');

        $r = $this->r->getContent($this->c);

        $this->assertEquals('while(1);'.json_encode(array(
                'status' => $this->status,
                'response' => $this->content
            )), $r);

        $this->assertEquals('text/javascript', b::response()->getContentType());

    }

}