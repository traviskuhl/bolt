<?php


class responseXhrTest extends bolt_test {

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

        $this->r = new \bolt\browser\response\xhr();
        $this->c = new \bolt\browser\controller();

        $this->html = 'this is html';
        $this->data = array('1'=>'2');
        $this->status = 200;

        $this->c->setContent($this->html);
        $this->c->setData($this->data);
        $this->c->setStatus($this->status);

    }

    public function testTestContenType() {
        $this->assertEquals(array('100' => 'text/javascript;text/xhr'), \bolt\browser\response\xhr::$contentType);
    }

    public function testGetContentNoScript() {
        $r = $this->r->getContent($this->c);

        $this->assertEquals(json_encode(array(
                'status' => $this->status,
                'response' => array(
                    'content' => $this->html,
                    'data' => $this->data,
                    'bootstrap' => array(
                        'javascript' => array()
                    )
                )
            )), $r);

        $this->assertEquals('text/javascript', b::response()->getContentType());

    }

    public function testGetContentWithScript() {


        $script = 'var some_javas_script = true;';
        $this->html = "<script>{$script}</script> with more html";

        $this->c->setContent($this->html);

        $r = $this->r->getContent($this->c);

        $this->assertEquals(json_encode(array(
                'status' => $this->status,
                'response' => array(
                    'content' => ' with more html',
                    'data' => $this->data,
                    'bootstrap' => array(
                        'javascript' => array($script)
                    )
                )
            )), $r);

        $this->assertEquals('text/javascript', b::response()->getContentType());

    }

}