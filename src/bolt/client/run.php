<?php

namespace bolt\client;
use \b;

b::command('run', '\bolt\client\run', array(
        'flags' => array(
            array('verbose|v', 'Turn on verbose logs')
        ),
        'options' => array(

        ),
    ));

class run extends \bolt\cli\command {

    public function run() {

        echo `php -S localhost:81`;

    }

}