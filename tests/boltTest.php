<?php
    
// include bootstrap
include("bootstrap.php");

class boltTest extends bolt_test {
    
    // setup
    public function testInit() {
            
        // a
        $a = array(
            'config' => array(
                'test' => true
            ),
            'core' => array(
                'config'
            ),
            'load' => array(
                dirname(__FILE__).'/_blank.php'
            )
        );                    
            
        // call init with some test stuff
        b::init($a);            
    
        // get our bolt instance
        $b = b::bolt();
        
        // test get bolt
        $this->assertInstanceOf('bolt', $b);
        
        // make sure they both have
        $this->assertEquals($a['core'], array_keys($b->getPlugins()));
        
        // loaded
        $this->assertEquals($a['load'], b::getLoaded());
        
        // config was set
        $this->assertTrue(b::config()->test);        
        
        // config
        $this->assertTrue(b::_("test"));

        // config
        $this->assertEquals(b::__("test2", true), true);
        
        // param
            
    }

}

?>