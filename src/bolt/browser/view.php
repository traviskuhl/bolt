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

    public function __construct($parent, $file, $layout, $vars) {
        $this->_parent = $parent;
        $this->_file = $file;
        $this->_layout = $layout;
        $this->_vars = $vars;
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

        $views = b::settings()->value("project.views", false);
        $root = b::config()->value("root", __DIR__);

        // check
        $check = array($file, b::path($root, $file));

        if (is_array($views)) {
            foreach ($views as $folder) {
                $check[] = b::path($root, $folder, $file);
            }
        }
        if (is_string($views)) {
            $check[] = b::path($root, $views, $file);
        }

        while (($file = array_shift($check)) !== null) {
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