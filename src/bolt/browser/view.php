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
            'file' => $this->_file,
            'self' => $this->_parent,
            'vars' => $this->_vars
        ));

        if ($this->_layout) {
            $this->_vars->set('yield', $html);
            $html = b::render(array(
                'file' => $this->_layout,
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

}