<?php


class responseAjaxTest extends bolt_test {

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

        $this->r = new \bolt\browser\response\ajax();
        $this->c = new \bolt\browser\controller();

        $this->data = array('1'=>'2');
        $this->status = 200;

        $this->c->setData($this->data);
        $this->c->setStatus($this->status);

    }

    public function testTestContenType() {
        $this->assertEquals(array('100' => 'text/javascript;text/ajax'), \bolt\browser\response\ajax::$contentType);
    }

    public function testGetContent() {
        $r = $this->r->getContent($this->c);

        $this->assertEquals(json_encode(array(
                'status' => $this->status,
                'response' => array(
                    'content' => false,
                    'data' => $this->data
                )
            )), $r);

        $this->assertEquals('text/javascript', b::response()->getContentType());

    }

}