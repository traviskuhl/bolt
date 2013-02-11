<?php

class configTest extends bolt_test {

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'core' => array('bucket')
        ));

        // data array
        $this->data = array(
            'string' => 'this is a string value',
            'no key',
            'array' => array(
                'no key',
                'key1' => 'key value 1',
                'key2' => 'key value 2',
                'nested' => array(
                    'key1' => 'nest key value 1',
                    'key2' => "nest by value 2",
                    'nest no key'
                )
            ),
            'bool' => true,
            'int' => 1,
            'float' => 1.1
        );

        $this->c = b::config($this->data);

    }

    public function testInit() {
        $this->assertTrue(is_a($this->c, '\bolt\config'));
    }
    public function testInitData() {
        $c = b::config($this->data);
        $d = $c->asArray();
        $this->assertEquals($d['bool'], $this->data['bool']);
    }
    public function testMagicGet() {
        $this->assertTrue($this->c->bool->value);
    }
    public function testMagicSet() {
        $this->c->poop = true;
        $this->assertTrue($this->c->poop->value);
    }
    public function testMagicCall() {
        $this->assertTrue($this->c->get('bool')->value);
    }

    // json
    public function testJsonFromString() {
        $c = b::config();
        $c->fromJson(json_encode($this->data));
        $this->assertTrue($c->get('bool')->value);
    }
    public function testJsonFromFileHandle() {
        $f = tmpfile();
        fwrite($f, json_encode($this->data));
        $c = b::config();
        $c->fromJson($f);
        $this->assertTrue($c->get('bool')->value);
    }
    public function testJsonFromFileName() {
        $f = tempnam(sys_get_temp_dir(), 'bolt-tests');
        file_put_contents($f, json_encode($this->data));
        $c = b::config();
        $c->fromJson($f);
        $this->assertTrue($c->get('bool')->value);
        unlink($f);
    }

    // yaml
    public function testYamlFromString() {
        if (!function_exists('yaml_emit')) {return;}
        $y = yaml_emit($this->data);
        $c = b::config();
        $c->fromYamlString($y);
        $this->assertTrue($c->get('bool')->value);
    }
    public function testYamlFromFileHandle() {
        if (!function_exists('yaml_emit')) {return;}
        $f = tempnam(sys_get_temp_dir(), 'bolt-tests');
        file_put_contents($f, yaml_emit($this->data));
        fopen($f, 'a');
        $c = b::config();
        $c->fromYamlFile($f);
        $this->assertTrue($c->get('bool')->value);
    }
    public function testYamlFromFileName() {
        if (!function_exists('yaml_emit')) {return;}
        $f = tempnam(sys_get_temp_dir(), 'bolt-tests');
        file_put_contents($f, yaml_emit($this->data));
        $c = b::config();
        $c->fromYamlFile($f);
        $this->assertTrue($c->get('bool')->value);
        unlink($f);
    }


}