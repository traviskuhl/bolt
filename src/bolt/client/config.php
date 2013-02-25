<?php

namespace bolt\client;
use \b;

b::command('settings', '\bolt\client\settings', array(
        'set' => array(
            'flags' => array(
                array("global|g", "Set variable as global")
            )
        )
    ));

class settings extends \bolt\cli\command {

    protected $handle = false;
    protected $file


    public function set() {
        if (count($this->getArgv()) == 0) {
            return $this->list();
        }

        list($file, $storage) = $this->getStorage();

    }

}