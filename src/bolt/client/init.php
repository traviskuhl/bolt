<?php

namespace bolt\client;
use \b;

b::command('init', '\bolt\client\init', array(
        'flags' => array(
            array('verbose|v', 'Turn on verbose logs')
        ),
        'options' => array(

        ),
    ));

class init extends \bolt\cli\command {

    public function run() {



    }

}