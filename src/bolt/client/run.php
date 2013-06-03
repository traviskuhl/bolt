<?php

namespace bolt\client;
use \b;


class run extends \bolt\cli\command {

    public static $name = 'run';
    public static $options = array(
        'hostname' => array(
            'short_name' => '-h',
            'long_name' => '--hostname',
            'description' => 'hostname',
            'default' => 'localhost',
        ),
        'port' => array(
            'short_name' => '-p',
            'long_name' => '--port',
            'description' => 'port',
            'default' => 8000,
            'action' => 'StoreInt'
        )
    );

    public function execute($src=".") {
        $src = realpath($src);

        // check our version
        if (version_compare(PHP_VERSION, 5.4) === -1) {
            $this->error("Your version of PHP (%s) does not have a built in server.\nVisit bolthq.com for more information.", PHP_VERSION);
        }

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
        $this->line(array(
            str_repeat("-", 40),
            "Starting server. Ctrl-C to exit.",
            array(" root: %s", $src),
            array(" host: %s", $this->hostname),
            array(" port: %d", $this->port)
        ));

        // put a router file in tmp for this run
        $temp = tmpfile();

        // get it's name
        $meta = stream_get_meta_data($temp);

        // write our stub
        fwrite($temp, $stub);

        // run
        $cmd = "php -S {$this->hostname}:{$this->port} {$meta['uri']}";

        // run
        echo `$cmd`;

        // and done
        fclose($temp); // this removes the file

        // move back
        chdir($cwd);

        // set it
        $this->done(array(
            str_repeat("-", 40),
            "Done! Server destroyed."
        ));

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