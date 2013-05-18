<?php


class responseXmlTest extends bolt_test {

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

        $this->r = new \bolt\browser\response\xml();
        $this->status = 200;
        $this->r->setStatus($this->status);

    }

    public function testTestContenType() {
        $this->assertEquals(array(100 => 'application/xml'), \bolt\browser\response\xml::$contentType);
    }

    public function testGetContent() {
        $r = $this->_setContent(array('a' => 'b'));

        $this->assertXmlStringEqualsXmlString("<a>b</a>", $r);

        $this->assertEquals('application/xml', $this->r->getContentType());

    }

    public function _setContent($content) {
        $this->r->setContent($content);
        return $this->r->handle();
    }

    public function testGetContentAttribute() {
        $r = $this->_setContent(array(
                'a' => array(
                    '@' => array('b' => 'c', 'd' => 'f'),
                    'g' => 'h',
                    'i' => null
                )
            ));
        $this->assertXmlStringEqualsXmlString('<a b="c" d="f"><g>h</g><i/></a>', $r);
    }

    public function testGetContentValue() {
        $r = $this->_setContent(array(
            'a' => array(
                '@' => array('c'=>'d'),
                '_value' => 'b'
            )
        ));
        $this->assertXmlStringEqualsXmlString('<a c="d">b</a>', $r);
    }

    public function testGetContentItem() {
        $r = $this->_setContent(array(
            'a' => array(
                array('_item' => 'b', 'c' => 'd'),
                array('_item' => 'b', 'c' => 'd'),
            )
        ));
        $this->assertXmlStringEqualsXmlString('<a><b><c>d</c></b><b><c>d</c></b></a>', $r);
    }

    public function testGetContentCData() {
        $r = $this->_setContent(array(
            '*a' => "b"
        ));
        $this->assertXmlStringEqualsXmlString('<a><![CDATA[b]]></a>', $r);
    }

}