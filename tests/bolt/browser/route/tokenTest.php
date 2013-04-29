<?php

use bolt\browser\route\token;

class routTokenParserTest extends bolt_test {

    private $i = false;

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'mode' => 'browser'
        ));

        // token
        $this->t = new token('test', 'none');

    }

    public function testMatch() {
        $this->assertTrue($this->t->match('test'));
    }
    public function testNoMatch() {
        $this->assertFalse($this->t->match('test2'));
    }

    public function testWithParams() {
        $this->t->setPath('test/{token1}/{token2}');
        $this->assertTrue($this->t->match('test/1/2'));
        $p = $this->t->getParams();
        $this->assertEquals(1, $p['token1']);
        $this->assertEquals(2, $p['token2']);
    }

    public function testNoMatchWithParams() {
        $this->t->setPath('test/{token1}/{token2}');
        $this->assertFalse($this->t->match('test/1'));
    }

    public function testWithInterParams() {
        $this->t->setPath('test/{name}.{ext}');
        $this->assertTrue($this->t->match('test/file.php'));
        $p = $this->t->getParams();
        $this->assertEquals('file', $p['name']);
        $this->assertEquals('php', $p['ext']);
    }

    public function testCustomValidatorNumber() {
        $this->t->setPath('test/{num}')->validate('num', '[0-9]+');
        $this->assertTrue($this->t->match('test/1'));
        $this->assertFalse($this->t->match('test/1/22'));
        $this->assertFalse($this->t->match('test/aa'));
        $this->assertFalse($this->t->match('test/1aa'));
    }

    public function testCustomValidatorPath() {
        $this->t->setPath('test/{path}')->validate('path', '.*');
        $this->assertTrue($this->t->match('test/path/long'));
        $this->assertFalse($this->t->match('nope/path/long'));
    }

}