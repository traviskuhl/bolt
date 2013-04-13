<?php

namespace bolt\client;
use \b;

b::command('init', '\bolt\client\init', array(
        'flags' => array(

        ),
        'options' => array(

        ),
    ));

class init extends \bolt\cli\command {

    public function run($packageFile=false) {
        $root = realpath(__DIR__);
        $package = false;

        // figure out what package we have
        // a local package
        if (file_exists($packageFile) AND is_readable($packageFile)) {
            $root = realpath(dirname($packageFile));
            $package = json_decode(file_get_contents($packageFile), true);
        }

        if (!is_array($package)) {
            return $this->err("Unable to parse package file (".$packageFile.").");
        }

        // where are we now
        $pwd = getcwd();

        // get dir root
        if (isset($package['directories']['_root'])) {
            if (substr($package['directories']['_root'], 0, 2) == './') {
                $package['directories']['_root'] = substr($package['directories']['_root'], 2);
            }

            $root = rtrim($root,'/').'/'.$package['directories']['_root'];
        }

        // create root
        if (!file_exists($root)) {
            mkdir($root, 0700, true);
        }

        // still no root
        if (!file_exists($root)) {
            return $this->err("Unable to find or create root ({$root}).");
        }

        // move to root
        chdir($root);

        // see if root has salt.txt
        if (!file_exists("./salt.txt")) {
            if ($this->askYesNo('There is no salt file in. Would you like us to create one?')) {
                file_put_contents("./salt.txt", b::randomString(100));
            }
        }

        // write our settings file
        if (isset($package['settings'])) {
            file_put_contents("settings.json", json_encode($package['settings']));
        }

        // loop through and write any directories
        if (isset($package['directories'])) {
            foreach ($package['directories'] as $name => $dir) {
                if ($name === '_root') { continue; }
                if (is_string($dir)) {
                    $dir = array(
                        'path' => $dir,
                        'mode' => 0700,
                        'user' => false,
                        'group' => false
                    );
                }
                if (file_exists($dir['path'])) { continue; }
                mkdir($dir['path'], $dir['mode'], true);
                if (isset($dir['user']) AND $dir['user']) {
                    chown($dir['path'], $dir['user']);
                }
                if (isset($dir['group']) AND $dir['group']) {
                    chgrp($dir['path'], $dir['group']);
                }
            }
        }

        $pconfig = bConfig . $package['name'] . '.ini';

        // make our package config file
        if (!file_exists(basename($pconfig))) {
            mkdir(basename($pconfig));
        }

        // add our package
        b::config()->get('global')->set($package['name'], array(
            'hostname' => array(),
            'load' => array(
                $root
            ),
            'salt' => realpath("./salt.txt"),
            'settings' => realpath("./settings.json"),
            'config' => $pconfig
        ));

        // to
        $ini = b::config()->toIniFile('global');

        $fp = fopen(bConfig."/config.ini", "r+");

        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, $ini);
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        else {
            return $this->err("Unable to write global config file.");
        }



    }

}