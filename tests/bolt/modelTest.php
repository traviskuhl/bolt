<?php

namespace {

class modelTest extends bolt_test {

    private $i = false;

    public function setUp() {

        // we need to int part of bolt
        b::init(array(
            'config' => array(
                'autoload' => array()
            ),
            'core' => array('model')
        ));
    }

    public function testPlaceholder() {
        $this->assertTrue(true);
    }

    // public function testFactory() {
    //     $this->assertTrue(is_a(b::dao('daoTestClass'), 'daoTestClass'));
    // }
    // public function testFactoryNamespace() {
    //     $this->assertTrue(is_a(b::dao('daoTestNamespace\daoTestNamespaceClass'), 'daoTestNamespace\daoTestNamespaceClass'));
    // }
    // public function testBadClassFactory() {
    //     $this->assertFalse(b::dao('notARealClass'));
    // }
    // public function testFactoryArgs() {
    //     $a = array('a','b','c');
    //     $dao = b::dao('daoTestClass', array($a, 'poop'));
    //     $this->assertEquals($a, $dao->getArgs());
    // }
    // public function testAddGetShortcut() {
    //     \bolt\dao::shortcut('test', 'daoTestClass');
    //     $s = \bolt\dao::getShortcuts();
    //     $this->assertEquals($s, array('test' => 'daoTestClass'));
    // }
    // public function testAddGetTraits() {
    //     \bolt\dao::traits('daoTestClass');
    //     $s = \bolt\dao::getTraits();
    //     $this->assertEquals($s, array('daoTestClass'));
    // }
    // public function testFactoryShortcut() {
    //     \bolt\dao::shortcut('test','daoTestClass');
    //     $this->assertTrue(is_a(b::dao('test'), 'daoTestClass'));
    // }

}

class modelTestClass {
    private $_args = array();
    public function __construct($args=array()) {
        $this->_args = $args;
    }
    public function getArgs() {
        return $this->_args;
    }
}

} // root namespace

namespace modelTestNamespace {

class modelTestNamespaceClass {


}

} // modelTestNamespace