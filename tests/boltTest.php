<?php

class boltTest extends bolt_test {

    private $_initSettings = false;

    public function setUp() {
        $this->_initSettings = array(
            'config' => array(
                'test' => true
            ),
            'load' => array(
                dirname(__FILE__).'/include/blankClass.php'
            )
        );

        // call init with some test stuff
        b::init($this->_initSettings);

    }

    // setup
    public function testInit() {

        // get our bolt instance
        $b = b::bolt();

        // test get bolt
        $this->assertInstanceOf('bolt', $b);

    }

    public function testLoad() {

        // test load on init
        $this->assertTrue(in_array($this->_initSettings['load'][0], b::getLoaded()));

        $this->assertTrue(class_exists("blankClass"));

        $file = dirname(__FILE__).'/include/blankClass2.php';

        // load array
        b::load(array($file));

        // load
        $this->assertTrue(in_array($file, b::getLoaded()));

        $this->assertTrue(class_exists("blankClass2"));

    }

    public function testAutoload() {

        $folder = dirname(__FILE__).'/include/';

        // add to autoload
        b::$autoload[] = $folder;

        // assert true
        $this->assertTrue(in_array($folder, b::$autoload));

        // does the class exists
        $this->assertTrue(class_exists("blankClass3", true));

    }

    public function testConfig() {


    }

}

?>