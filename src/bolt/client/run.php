<?php

namespace bolt\client;
use \b;


class run extends \bolt\cli\command {

    public static $name = 'run';

    public static function commands() {
        return array(
            'test' => array(

            )
        );
    }

    public function execute($src=".") {
        $src = realpath($src);

        // args
        $args = array(
            'config' => array(
                'root' => realpath($src)
            ),
            'load' => array(
                "{$src}/../handlebars.php/Handlebars/",
                realpath($src)
            )
        );

        // where are we now
        $cwd = getcwd();

        // move into src
        chdir($src);

        // check for some things
        if (file_exists("templates")) {
            $args['config']['templates'] = realpath("./templates");
        }
        if (file_exists("templates/_partials")) {
            $args['config']['partials'] = realpath("./templates/_partials");
        }

        // stub
        $stub = $this->_getRouterStub($args);

        // set it
        echo str_repeat("-", 40);
        echo "\nStarting server. Ctrl-C to exit.\n";
        echo " root: $src\n\n";

        // put a router file in tmp for this run
        $temp = tmpfile();

        // get it's name
        $meta = stream_get_meta_data($temp);

        // write our stub
        fwrite($temp, $stub);

        // run
        $cmd = "php -S localhost:8000 {$meta['uri']}";

        // run
        echo `$cmd`;

        // and done
        fclose($temp); // this removes the file

        // move back
        chdir($cwd);

    }


    private function _getRouterStub($args) {

        // add our mode
        $args['mode'] = 'browser';

        // make our stub
        return '<?php

            $_SERVER["PATH_INFO"] = $_SERVER["REQUEST_URI"];

            error_reporting(E_ALL^E_DEPRECATED);
            ini_set("display_errors",1);

            // require
            require "bolt.phar";

            // init from server request
            b::init('.var_export($args, true).');

            // run in cli mode
            b::run();

        ';
    }

}