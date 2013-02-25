<?php

namespace bolt\client;
use \b;

b::command('init', '\bolt\client\init', array(
        'flags' => array(
            array('verbose|v', 'Turn on verbose logs')
        ),
        'options' => array(
            array('stub|s', array('default' => 'poop', 'description' => 'Output only the stub'))
        ),

        'set' => array(

        )
    ));

class init extends \bolt\cli\command {

    public function run() {

        var_dump('pooper', $this->stub); die;

    }

}