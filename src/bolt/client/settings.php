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

    protected $handle = false;
    protected $file

    protected function openStorage() {
        if ($this->global) {
            $file = bGlobalConfig;
        }
        else {
            // loop until we find a file
            $i = 0;
            while ($i++ < 10) {
                $file = realpath('./.bolt/config');
                if (file_exists($file)) {
                    break;
                }
            }
        }
        if (!$file) { return false; }

        $this->handle = fopen($file, "w+");


        $json = json_decode($data, true);
        return array($file, b::bucket($json));
    }

    protected function closeStorage($file, $bucket) {


    }

    public function set() {
        if (count($this->getArgv()) == 0) {
            return $this->list();
        }

        list($file, $storage) = $this->getStorage();

    }

}