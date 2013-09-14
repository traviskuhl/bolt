<?php

namespace bolt\browser;
use \b;

interface iView {

    public function render();

    public function setFile($file);
    public function setLayoutFile($file);
    public function setVars($vars);
    public function setParent($parent);

}


class view implements iView {
    private $_file;
    private $_vars;
    private $_layout = false;
    private $_parent;

    // compield
    private static $_compiled = false;

    public function __construct($parent, $file, $layout, $vars) {
        $this->_parent = $parent;
        $this->_file = $file;
        $this->_layout = $layout;
        $this->_vars = $vars;

        // no
        if (self::$_compiled === false AND b::package()->getDirectories('compiled')) {
            $c = b::package()->getDirectories('compiled');
            if (array_key_exists('views', $c) AND file_exists($c['views'])) {
                self::$_compiled = include($c['views']);
            }
            else {
                self::$_compiled = array();
            }
        }
    }

    public function render() {


        $html = b::render(array(
            'file' => $this->_getFilePath($this->_file),
            'self' => $this->_parent,
            'vars' => $this->_vars
        ));

        if ($this->_layout) {
            $this->_vars->set('yield', $html);
            $html = b::render(array(
                'file' => $this->_getFilePath($this->_layout),
                'self' => $this->_parent,
                'vars' => $this->_vars
            ));
        }

        return $html;

    }

    public function setParent($parent) {
        $this->_parent = $parent;
        return $this;
    }

    public function setFile($file) {
        $this->_file = $file;
        return $this;
    }

    public function setLayoutFile($file) {
        $this->_layout = $file;
        return $this;
    }

    public function setVars($vars) {
        $this->_vars = $vars;
        return $this;
    }

    private function _getFilePath($file) {

        // settings?
        $views = b::settings()->value("project.views", false);
        $roots = b::package()->getDirectories('views');

        // check
        $checkWithRoot = array($file);
        $check = array($file);

        if (is_array($views)) {
            foreach ($views as $folder) {
                foreach ($roots as $root) {
                    $checkWithRoot[] = b::path($root, $folder, $file);
                }
                $check[] = b::path($folder, $file);
            }
        }
        if (is_string($views)) {
            $check[] = b::path($views, $file);
            $checkWithRoot[] = b::path($views, $file);
        }

        // check
        if (self::$_compiled) {
            foreach ($check as $name) {
                $name = b::path($name);
                if (array_key_exists($name, self::$_compiled)) {
                    return self::$_compiled[$name];
                }
            }
        }

        while (($file = array_shift($checkWithRoot)) !== null) {
            if (is_file($file)) {
                return $file;
            }
        }

        // bad!
        return $file;

    }

    public function __invoke() {
        return $this->render();
    }

}