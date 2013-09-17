<?php

// bolt namespace
namespace bolt;
use \b;


// our settings is singleton
class package {

    private $_pkg = array();
    private $_file = false;

    public function __construct($file) {
        $this->_pkg = json_decode(file_get_contents($file), true);
        $this->_file = realpath($file);
        $this->_root = dirname($this->_file);
    }

    public function getConfig() {
        return (array_key_exists('config', $this->_pkg) ? $this->_pkg['config'] : array());
    }

    public function getRoot() {
        return $this->_root;
    }

    public function getDirectorieRoot($type) {
        $base = b::param("root", "", $this->_pkg['directories']);
        $root = "";

        if ($type AND array_key_exists('directories', $this->_pkg) AND  array_key_exists($type, $this->_pkg['directories'])) {
            $item = $this->_pkg['directories'][$type];
            if (count($item) == 0) {return array();}
            $root = "";

            // root
            if (is_array($item[0]) AND key($item[0]) == 'root') {
                $root = $item[0]['root'];
            }
        }

        return b::path($this->_root, $base, $root);
    }

    // getDirectories
    public function getDirectories($type=false, $useRoot=true) {
        $dirs = array();
        $base = ($useRoot === true ? b::param("root", "", $this->_pkg['directories']) : "");


        if ($type AND array_key_exists('directories', $this->_pkg) AND  array_key_exists($type, $this->_pkg['directories'])) {
            $item = $this->_pkg['directories'][$type];
            if (count($item) == 0) {return array();}
            $root = "";


            // root
            if (isset($items[0]) AND is_array($item[0]) AND key($item[0]) == 'root') {
                $root = $item[0]['root'];
                unset($item[0]);
            }

            foreach ($item as $i => $dir) {
                if (is_dir($dir)) {
                    $dirs[$i] = $dir;
                }
                else {
                    $dirs[$i] = b::path($base, $root, $dir);
                }
            }

        }
        else if (!$type AND array_key_exists('directories', $this->_pkg)) {
            $dirs = $this->_pkg['directories'];
        }


        return $dirs;
    }

    // get settings
    public function getSettings() {
        $settings = array();
        if (array_key_exists("settings", $this->_pkg)) {
            foreach ($this->_pkg['settings'] as $item) {
                if (is_array($item)) {
                    $settings = array_merge($settings, $item);
                }
            }

        }
        return $settings;
    }


}
