<?php

class bArrayTest extends bolt_test {

    private $i = false;

    public function setUp() {

        b::depend('bolt-core-bucket-*');

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
            'float' => 1.1,
            'object' => new StdClass()
        );

        $this->bucket = new \bolt\bucket\bArray($this->data);

    }

    public function testBGuid() {
        $this->assertTrue(is_string($this->bucket->bGuid()));
    }

    public function testValueWithKey() {
        $this->assertEquals($this->data['string'], $this->bucket->value('string'));
    }

    public function testValueWithKeyDefault() {
        $this->assertTrue($this->bucket->value('does not exist', true));
    }

    public function testValueWithNoKey() {
        $this->assertEquals($this->data, $this->bucket->value());
    }

    public function testNormalize() {
        $this->assertEquals($this->data, $this->bucket->normalize());
    }

    public function testGetWithValue() {
        $this->assertInstanceOf('\bolt\bucket\bString', $this->bucket->get('string'));
        $this->assertInstanceOf('\bolt\bucket\bObject', $this->bucket->get('object'));
        $this->assertInstanceOf('\bolt\bucket\bArray', $this->bucket->get('array'));
    }

    public function testGetWithNoValue() {
        $r = $this->bucket->get('no value', true);
        $this->assertInstanceOf('\bolt\bucket\bString', $r);
        $this->assertTrue($r->value);
    }

    public function testGetWithNoValueNoDefault() {
        $r = $this->bucket->get('no value');
        $this->assertInstanceOf('\bolt\bucket\bString', $r);
        $this->assertFalse($r->value);
    }

    public function testGetDotNotation() {
        $r = $this->bucket->get('array.key1');
        $this->assertInstanceOf('\bolt\bucket\bString', $r);
        $this->assertEquals($this->data['array']['key1'], $r->value);
    }

    public function testGetDotNotationDefault() {
        $r = $this->bucket->get('array.nokey1', true);
        $this->assertInstanceOf('\bolt\bucket\bString', $r);
        $this->assertTrue($r->value);
    }

    public function testSetSingleValue() {
        $self = $this->bucket->set("new", 'poop');
        $this->assertInstanceOf('\bolt\bucket\bArray', $self);
        $this->assertEquals($self->bGuid(), $this->bucket->bGuid());
        $this->assertEquals('poop', $self->value('new'));
    }

    public function testSetArray() {
        $self = $this->bucket->set(array('new1' => 'poop1', 'new2' => 'poop2'));
        $this->assertInstanceOf('\bolt\bucket\bArray', $self);
        $this->assertEquals($self->bGuid(), $this->bucket->bGuid());
        $this->assertEquals('poop1', $self->value('new1'));
        $this->assertEquals('poop2', $self->value('new2'));
    }

    public function testRemoveSingleValue() {
        $this->bucket->set(array('new1' => 'poop1', 'new2' => 'poop2'));
        $self = $this->bucket->remove('new1');
        $this->assertInstanceOf('\bolt\bucket\bArray', $self);
        $this->assertEquals($self->bGuid(), $this->bucket->bGuid());
        $this->assertFalse($this->bucket->get('new1')->value);
    }

    public function testRemoveNultipleValue() {
        $this->bucket->set(array('new1' => 'poop1', 'new2' => 'poop2'));
        $self = $this->bucket->remove(array('new1','new2'));
        $this->assertInstanceOf('\bolt\bucket\bArray', $self);
        $this->assertEquals($self->bGuid(), $this->bucket->bGuid());
        $this->assertFalse($this->bucket->get('new1')->value);
        $this->assertFalse($this->bucket->get('new2')->value);
    }

    public function testAsJsonString() {
        $this->assertEquals(json_encode($this->data), $this->bucket->asJson());
    }

    public function testAsSerialized() {
        $this->assertEquals(serialize($this->data), $this->bucket->asSerialized());
    }

    // // global properties
    // public function testAsArray() {
    //     $this->assertEquals($this->data, $this->bucket->asArray());
    // }

    // public function testAsJson() {
    //     $this->assertEquals(json_encode($this->data), $this->bucket->asJson());
    // }
    // public function testAsSerialized() {
    //     $this->assertEquals(serialize($this->data), $this->bucket->asSerialized());
    // }

    // // dot notation get
    // public function testDotNotationGet() {
    //     $this->assertEquals('nest key value 1', $this->bucket->value('array.nested.key1'));
    // }
    // public function testDotNotationGetFail() {
    //     $this->assertFalse($this->bucket->value('array.nested.key1.nothing'));
    // }
    // public function testDotNotationGetFailDefault() {
    //     $this->assertTrue($this->bucket->value('array.nested.key1.nothing', true));
    // }

    // // magic methods
    // public function testMangicToString() {
    //     $this->assertEquals(json_encode($this->data), (string)$this->bucket);
    // }
    // public function testMagicGet() {
    //     $this->assertTrue((bool)$this->bucket->bool);
    // }
    // public function testMangicSet() {
    //     $b = b::bucket();
    //     $b->poop = true;
    //     $this->assertTrue($b->poop->value);
    // }
    // public function  testMagicIsset() {
    //     $this->assertTrue(isset($this->bucket->string), true);
    // }


    // /// get methods
    // public function testGet() {
    //     $this->assertTrue((bool)$this->bucket->get('bool'));
    // }
    // public function testGetValue() {
    //     $this->assertTrue($this->bucket->value('bool'));
    // }
    // public function testGetValueDefault() {
    //     $this->assertEquals($this->bucket->value('poop', 'def'), 'def');
    // }
    // public function testNestedGet() {
    //     $this->assertEquals((string)$this->bucket->array->key1, 'key value 1');
    // }
    // public function testGetValueProperty() {
    //     $this->assertTrue($this->bucket->bool->value);
    // }



    // /// set methods
    // public function testSet() {
    //     $b = b::bucket();
    //     $b->set('poop', true);
    //     $this->assertTrue($b->poop->value);
    // }
    // public function testNestedSet() {
    //     $b = b::bucket(array('nested'=>array()));
    //     $b->nested->poop = true;
    //     $this->assertTrue($b->nested->poop->value); // nested set
    //     $this->assertEquals($b->asArray(), array('nested'=>array('poop' => true))); // nested set parent
    // }



    // // array methods
    // public function testPush() {
    //     $b = b::bucket();
    //     $b->push('poop');
    //     $this->assertTrue(in_array('poop', $b->asArray()));
    // }
    // public function testIn() {
    //     $b = b::bucket(array('poop'));
    //     $this->assertTrue($b->in('poop'));
    // }
    // public function testMap() {
    //     $b = b::bucket(array('one','two','three'));
    //     $b->map(function($key, $value, $o){
    //         $o->$key = $value.'A';
    //     });
    //     $this->assertEquals(array('oneA','twoA','threeA'), $b->asArray());
    // }
    // public function testExists() {
    //     $this->assertTrue($this->bucket->exists('bool'));
    // }
    // public function testItem() {
    //     $b = b::bucket(array('first_item'=>'item', 'item2'));
    //     $this->assertEquals($b->item(0), 'item2');
    //     $this->assertEquals($b->item('first_item'), 'item');
    // }
    // public function testItemFirst() {
    //     $b = b::bucket(array('first_item'=>'item', 'last_item' => 'item2'));
    //     $this->assertEquals($b->item('first'), 'item');
    // }
    // public function testItemLast() {
    //     $b = b::bucket(array('first_item'=>'item', 'last_item' => 'item2'));
    //     $this->assertEquals($b->item('last'), 'item2');
    // }
    // public function testOffsetSet() {
    //     $id = uniqid();
    //     $this->bucket[$id] = 'poop';
    //     $this->assertEquals($this->bucket->$id, 'poop');
    // }
    // public function testOffsetExists() {
    //     $id = uniqid();
    //     $this->bucket[$id] = 'poop';
    //     $this->assertTrue(isset($this->bucket[$id]));
    // }
    // public function testOffsetUnset() {
    //     $id = uniqid();
    //     $this->bucket[$id] = 'poop';
    //     unset($this->bucket[$id]);
    //     $this->assertFalse(isset($this->bucket[$id]));
    // }
    // public function testOffsetGet() {
    //     $this->assertTrue($this->bucket['bool']->value);
    // }
    // public function testArrayAccess() {
    //     $data = array('one','two','three');
    //     $b = b::bucket($data);
    //     $test = array();
    //     foreach ($b as $item) {
    //         $test[] = $item;
    //     }
    //     $this->assertEquals($data, $test);
    // }
    // public function testNestedOffsetGet() {
    //     $this->assertEquals($this->bucket['array']['nested']['key1'], 'nest key value 1');
    // }

}