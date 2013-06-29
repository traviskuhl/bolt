<?php

namespace bolt\client;
use \b;

class init extends \bolt\cli\command {

    public static $name = 'init';
    public static $options = array(
        'hostname' => array(
            'short_name' => '-h',
            'long_name' => '--hostname',
            'description' => 'hostname',
            'default' => '',
        )
    );

    public function execute($packageFile=false, $root=false) {
        $package = $src = false;

        var_dump($packageFile, $root, $this->hostname); die;

        // where are we now
        $pwd = getcwd();

        // make sure config is writeable
        if (!is_writable(bConfig)) {
            return $this->err("Bolt configuration directory (".bConfig.") is not writeable.");
        }

        // figure out what package we have
        // a local package
        if (file_exists($packageFile) AND is_file($packageFile) AND is_readable($packageFile)) {
            $package = json_decode(file_get_contents($packageFile), true);
            $root = realpath(".");
        }
        else if (is_dir($packageFile) AND !$root) {
            return $this->err("A folder name '$packageFile' already exists in '$pwd'.");
        }
        else {
            if (stripos($packageFile, '/') === false AND $this->yes === false AND $this->askYesNo("You project name doesn't have a namespace (:namespace/:project). Continue") === false ) {
                return $this->err("Stopping. Try adding a namespace");
            }
            $package = b::client()->defaultPackage();
            $package['name'] = $packageFile;
            $root = $root ? realpath($root) : "{$pwd}/{$packageFile}";
        }

        if (!is_array($package)) {
            return $this->err("Unable to parse package file (".$packageFile.").");
        }

        // create root
        if (!file_exists($root)) {
            mkdir($root, 0755, true);
            chown($root, b::client()->getUser());
            chgrp($root, b::client()->getUser());
        }

        // still no root
        if (!file_exists($root)) {
            return $this->err("Unable to find or create root ({$root}).");
        }

        // root
        $package['config']['root'] = $root;

        // move to root
        chdir($root);

        // see if root has salt.txt
        if (!file_exists("./salt.txt")) {
            if ($this->yes === true OR $this->askYesNo('There is no salt file in. Would you like us to create one?') ) {
                $package['config']['salt'] = b::randomString(100);
            }
        }
        else {
            $package['config']['salt'] = trim(file_get_contents("./salt.txt"));
        }

        // write our settings file
        if (isset($package['settings'])) {
            file_put_contents("settings.json", json_encode($package['settings']));
            chmod("settings.json", 0666);
            chown("settings.json", b::client()->getUser());
            chgrp("settings.json", b::client()->getUser());
        }

        // loop through and write any directories
        if (isset($package['directories'])) {
            foreach ($package['directories'] as $name => $dir) {
                if ($name === '_root') { continue; }
                if (is_string($dir)) {
                    $dir = array(
                        'path' => $dir,
                        'mode' => false,
                        'user' => b::client()->getUser(),
                        'group' => b::client()->getUser()
                    );
                }
                if (file_exists($dir['path'])) { continue; }

                mkdir($dir['path'], ( (isset($dir['mode']) AND $dir['mode']) ? $dir['mode'] : 0755), true);

                if (isset($dir['user']) AND $dir['user']) {
                    chown($dir['path'], $dir['user']);
                }
                if (isset($dir['group']) AND $dir['group']) {
                    chgrp($dir['path'], $dir['group']);
                }
            }
        }

        // directories to add to config
        foreach (array('controllers','templates','partials','views','assets') as $dir) {
            if (array_key_exists($dir, $package['directories'])) {
                $package['config'][$dir] = $root . "/" . $package['directories'][$dir];
            }
        }

        $pconfig = bConfig . $package['name'] . '.ini';

        // make our package config file
        if (!file_exists(dirname($pconfig))) {
            mkdir(dirname($pconfig), 0755, true);
        }

        $ini = b::bucket($package['config'])->asIni();

        // place

        // unset our server sepecifc stuff
        unset($package['config']);

        // write our package file back
        file_put_contents("package.json", json_encode($package));
        chmod("package.json", 0666);
        chown("package.json", b::client()->getUser());
        chgrp("package.json", b::client()->getUser());

        //
        return $this->out("New project created! Enjoy!\n");

    }



}