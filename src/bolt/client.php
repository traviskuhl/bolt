<?php

namespace bolt;
use \b;

b::plug('client', '\bolt\client');

// when we run
b::on("ready", function(){
    b::client()->start();
});

class client extends \bolt\plugin\singleton {

    public function start() {

        if (file_exists("package.json")) {
            b::package(new \bolt\package(realpath("./package.json")));
        }

        // get our settings
        $home = b::param('SUDO_HOME', b::param('HOME', false, $_SERVER), $_SERVER);
        $user = b::param('SUDO_USER', b::param('USER', false, $_SERVER), $_SERVER);

        // home
        if ($home === false) {
            $home = "/home/{$user}";
        }
        if (!file_exists("{$home}/.bolt")) {
            mkdir("{$home}/.bolt/");
        }

    }

    public function getUser() {
        return b::param('SUDO_USER', b::param('USER', false, $_SERVER), $_SERVER);
    }

    protected function defaultPackage() {
        return array(
            'name' => false,
            'version' => '1.0',
            'description' => '',
            'keywords' => array(),
            'homepage' => "",
            'license' => "",
            "authors" => array(),
            "require" => array(
                "php" => ">=5.3.0"
            ),
            "dependencies" => array(),
            "files" => array(
            ),
            "directories" => array(
                "assets" => "assets",
                "views" => "views",
                "partials" => "views/_partials",
                "controllers" => "controllers",
                "models" => "models",
                "lib" => "lib"
            ),
            "settings" => array(

            ),
            "config" => array(
                "root" => false
            )
        );
    }
}
