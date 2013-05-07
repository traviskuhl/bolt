<?php

namespace bolt;
use \b;

b::plug('client', '\bolt\client');

class client extends \bolt\plugin\singelton {

    public function start() {

        // get our settings
        $home = p('SUDO_HOME', p('HOME', false, $_SERVER), $_SERVER);
        $user = p('SUDO_USER', p('USER', false, $_SERVER), $_SERVER);

        // home
        if ($home === false) {
            $home = "/home/{$user}";
        }
        if (!file_exists("{$home}/.bolt")) {
            mkdir("{$home}/.bolt/");
        }

        // settings
        b::setSettings('client', "{$home}/.bolt/settings.json");

        // when we're done, save settings
        b::on('destruct', function(){
            b::getSettings('client')->save();
        });

    }

}