<?php

include(dirname(__FILE__)."/../bootstrap.php");

class pluginTest extends bolt_test {

    private $i = false;
    
    public function setUp() {
        
        // we need to int part of bolt
        b::init(array(
            'config' => array(
                'autoload' => array()
            ),
            'core' => array('config')
        ));
    
        // new instance
        $this->i = new pluginTestClass();
            
    
    }
    
    public function testConstruct() {
    
        // we have an instance
        $this->assertInstanceOf('\bolt\plugin', $this->i);
        
    }

    public function testPlug() {
    
        // add a plugin
        $this->assertTrue($this->i->plug('testSingle', 'pluginTestPlugSingleton'));
        $this->assertTrue($this->i->plug('testFactory', 'pluginTestPlugFactory'));    
    
        // check that their both there
        $this->assertEquals(2, count($this->i->getPlugins()));
    
    } 
    
    public function testSetFallbacks() {

        // set it 
        $this->i->setFallbacks("pluginTestFallbackClass");
    
        // it's there
        $this->assertTrue(in_array("pluginTestFallbackClass",$this->i->getFallbacks()));
        
    }

    public function testGetFallbacks() {

        // set it 
        $this->i->setFallbacks("pluginTestFallbackClass");
    
        // it's there
        $this->assertTrue(in_array("pluginTestFallbackClass",$this->i->getFallbacks()));
        
    }
    
    // test call with fallback
    public function testCallFallback(){ 
    
        // set it 
        $this->i->setFallbacks("pluginTestFallbackClass");    
        
        $this->assertTrue($this->i->call('fallback'));
    
    }
    
    // test signel
    public function testCallSingletonInstance() {
  
        $this->assertTrue($this->i->plug('testSingle', 'pluginTestPlugSingleton'));  
        
        // get it 
        $i = $this->i->call('testSingle'); // testSingleton'
        
        // now call
        $this->assertInstanceof('pluginTestPlugSingleton', $i);
        
    }

    // test signel
    public function testCallSingletonMethod() {
  
        $this->assertTrue($this->i->plug('testSingle', '\pluginTestPlugSingleton'));  
        $this->assertTrue($this->i->plug('testSingleMethod', 'testSingle::testSingleton'));  
        
        // now call
        $this->assertTrue($this->i->call('testSingleMethod'));
        
    }
    
    // test single method by arg
    public function testCallSingletonMethodByArg() {
  
        $this->assertTrue($this->i->plug('testSingle', '\pluginTestPlugSingleton'));  
        
        // now call
        $this->assertTrue($this->i->call('testSingle', array('testSingleton')));
        
    }    
    
    // test single _default
    public function testCallSingletonDefault() {
  
        $this->assertTrue($this->i->plug('testSingleDefault', '\pluginTestPlugSingletonDefault'));  
        
        // now call
        $this->assertTrue($this->i->call('testSingleDefault'));
        
    }        

    // test factory
    public function testCallFactory() {
  
        $this->assertTrue($this->i->plug('testFact', '\pluginTestPlugFactory'));  
        
        // factory
        $f = $this->i->call('testFact', array());
        
        // now call
        $this->assertInstanceof('pluginTestPlugFactory', $f);
        
        // call a func
        $this->assertTrue($f->testFactory());
        
    }            
    
}

class pluginTestClass extends \bolt\plugin {

}

class pluginTestPlugSingleton extends \bolt\plugin\singleton {
    public function testSingleton() {
        return true;
    }    
}

class pluginTestPlugSingletonDefault extends \bolt\plugin\singleton {
    public function _default() {
        return true;
    }    
}

class pluginTestPlugFactory extends \bolt\plugin\factory {
    public function testFactory() {
        return true;
    }
}

class pluginTestFallbackClass {
    
    public static function fallback() {
        return true;
    }

}