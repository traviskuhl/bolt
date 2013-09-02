<?php

namespace bolt\client;
use \b;

class init extends \bolt\cli\command {

    public static $name = 'init';
    public static $options = array(

    );

    public function execute($name, $root=".", $src="https://github.com/traviskuhl/bolt-scaffold.git") {
        $pwd = getcwd();

        // is root a folder
        if (!file_exists($root)) {
            mkdir($root, null, true);
        }

        // root isn't a directory
        if (!file_exists($root)) {
            $this->line("'%s' is not a directory", $root);
            return;
        }

        // clone the repo locally
        `git clone $src .`;

        // make sure it checkedout correct
        if (!file_exists("$root/.git")) {
            $this->line("Unable to clone project directory");
            return;
        }

        //
        return $this->out("New project created! Enjoy!\n");

    }



}