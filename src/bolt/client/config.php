<?php

namespace bolt\client;
use \b;

b::command('config', '\bolt\client\config', array(
        'set' => array(
            'flags' => array(
                array("global|g", "Set variable as global")
            )
        )
    ));

class config extends \bolt\cli\command {


    public function run() {

        // get config data
        $config = b::config()->asArray();

        $this->out(b::jsonPretty($config));

    }


    public function set() {
        if (count($this->getArgv()) == 0) {
            return $this->list();
        }

        list($file, $storage) = $this->getStorage();

    }

}