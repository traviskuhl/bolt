<?php

class bucketTest extends bolt_test {

    private $i = false;

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'config' => array(
                'autoload' => array()
            ),
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

        $this->bucket = b::bucket($this->data);

    }

    // global properties
    public function testAsArray() {
        $this->assertEquals($this->data, $this->bucket->asArray());
    }
    public function testGetData() {
        $this->assertEquals($this->data, $this->bucket->getData());
    }
    public function testAsJson() {
        $this->assertEquals(json_encode($this->data), $this->bucket->asJson());
    }
    public function testAsSerialized() {
        $this->assertEquals(serialize($this->data), $this->bucket->asSerialized());
    }

    // dot notation get
    public function testDotNotationGet() {
        $this->assertEquals('nest key value 1', $this->bucket->get('array.nested.key1'));
    }
    public function testDotNotationGetFail() {
        $this->assertFalse($this->bucket->getValue('array.nested.key1.nothing'));
    }
    public function testDotNotationGetFailDefault() {
        $this->assertTrue($this->bucket->getValue('array.nested.key1.nothing', true));
    }

    // magic methods
    public function testMangicToString() {
        $this->assertEquals(json_encode($this->data), (string)$this->bucket);
    }
    public function testMagicGet() {
        $this->assertTrue((bool)$this->bucket->bool);
    }
    public function testMangicSet() {
        $b = b::bucket();
        $b->poop = true;
        $this->assertTrue($b->poop->value);
    }
    public function  testMagicIsset() {
        $this->assertTrue(isset($this->bucket->string), true);
    }


    /// get methods
    public function testGet() {
        $this->assertTrue((bool)$this->bucket->get('bool'));
    }
    public function testGetValue() {
        $this->assertTrue($this->bucket->getValue('bool'));
    }
    public function testGetValueDefault() {
        $this->assertEquals($this->bucket->getValue('poop', 'def'), 'def');
    }
    public function testNestedGet() {
        $this->assertEquals((string)$this->bucket->array->key1, 'key value 1');
    }
    public function testGetValueProperty() {
        $this->assertTrue($this->bucket->bool->value);
    }
    public function testModifiers() {
        $s = $this->bucket->string;
        foreach ($s->getModifiers() as $mod) {
            $this->assertEquals($s->$mod, $s->$mod());
        }
        $this->assertEquals($s->toupper, strtoupper($s));
    }



    /// set methods
    public function testSet() {
        $b = b::bucket();
        $b->set('poop', true);
        $this->assertTrue($b->poop->value);
    }
    public function testSetValue() {
        $b = b::bucket();
        $b->setValue('poop', 'yes');
        $this->assertEquals($b->poop, 'yes');
    }
    public function testNestedSet() {
        $b = b::bucket(array('nested'=>array()));
        $b->nested->poop = true;
        $this->assertTrue($b->nested->poop->value); // nested set
        $this->assertEquals($b->asArray(), array('nested'=>array('poop' => true))); // nested set parent
    }



    // array methods
    public function testPush() {
        $b = b::bucket();
        $b->push('poop');
        $this->assertTrue(in_array('poop', $b->asArray()));
    }
    public function testIn() {
        $b = b::bucket(array('poop'));
        $this->assertTrue($b->in('poop'));
    }
    public function testMap() {
        $b = b::bucket(array('one','two','three'));
        $b->map(function($key, $value, $o){
            $o->$key = $value.'A';
        });
        $this->assertEquals(array('oneA','twoA','threeA'), $b->asArray());
    }
    public function testExists() {
        $this->assertTrue($this->bucket->exists('bool'));
    }
    public function testItem() {
        $b = b::bucket(array('first_item'=>'item', 'item2'));
        $this->assertEquals($b->item(0), 'item2');
        $this->assertEquals($b->item('first_item'), 'item');
    }
    public function testItemFirst() {
        $b = b::bucket(array('first_item'=>'item', 'last_item' => 'item2'));
        $this->assertEquals($b->item('first'), 'item');
    }
    public function testItemLast() {
        $b = b::bucket(array('first_item'=>'item', 'last_item' => 'item2'));
        $this->assertEquals($b->item('last'), 'item2');
    }
    public function testOffsetSet() {
        $id = uniqid();
        $this->bucket[$id] = 'poop';
        $this->assertEquals($this->bucket->$id, 'poop');
    }
    public function testOffsetExists() {
        $id = uniqid();
        $this->bucket[$id] = 'poop';
        $this->assertTrue(isset($this->bucket[$id]));
    }
    public function testOffsetUnset() {
        $id = uniqid();
        $this->bucket[$id] = 'poop';
        unset($this->bucket[$id]);
        $this->assertFalse(isset($this->bucket[$id]));
    }
    public function testOffsetGet() {
        $this->assertTrue($this->bucket['bool']->value);
    }
    public function testArrayAccess() {
        $data = array('one','two','three');
        $b = b::bucket($data);
        $test = array();
        foreach ($b as $item) {
            $test[] = $item;
        }
        $this->assertEquals($data, $test);
    }
    public function testNestedOffsetGet() {
        $this->assertEquals($this->bucket['array']['nested']['key1'], 'nest key value 1');
    }

}