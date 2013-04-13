<?php

// bolt namespace
namespace bolt;
use \b;

// plugin to b
b::plug('config', "\bolt\config");

// our config is singleton
class config extends plugin\singleton {

    // data
    private $_bucket;

    public function __construct() {
        $this->_bucket = b::bucket();
    }

    // default
    public function _default($data=array()) {
        if ($data) {
            $this->_bucket->set($data);
        }
        return $this;
    }

    // __get
    public function __get($name) {
        return $this->get($name);
    }

    // __set
    public function __set($name, $value) {
        return $this->set($name, $value);
    }

    public function __call($name, $args) {
        return call_user_func_array(array($this->_bucket, $name), $args);
    }

    public function asArray() {
        return $this->_bucket->asArray();
    }

    // import
    public function import($file, $args=array()) {
        $parts = explode(".", $file);
        $key = p('key', false, $args);
        $data = array();
        switch(strtolower(array_pop($parts))) {
            case 'json':
                $data = $this->fromJson($file); break;
            case 'yaml':
                $data = $this->fromYamlFile($file); break;
            case 'ini':
                $data = $this->fromIniFile($file); break;
        };
        $this->_bucket->set(($key ? array($key => $data) : $data));
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief import from a jason string or file
    ///
    /// @param $from string or file to get from
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function fromJson($from) {
        $data = array();
        if (is_resource($from)) {
            $str = $buffer = false;
            while (($buffer = fgets($from, 4096)) !== false) {
                $str .= $buffer;
            }
            fclose($from);
            $data = json_decode($str, true);
        }
        else if ($from{0} == '{' OR $from{0} == '[') {
            $data = json_decode($from, true);
        }
        else if (file_exists($from)) {
            $data = json_decode(file_get_contents($from), true);
        }
        return $data;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief import from a yaml file
    ///
    /// @param $from file to get from
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function fromYamlFile($from) {
        $data = array();
        if (function_exists('yaml_parse_file')) {
            if (is_resource($from)) {
                $str = $buffer = false;
                while (($buffer = fgets($from, 4096)) !== false) {
                    $str .= $buffer;
                }
                fclose($from);
                $data = yaml_parse($str);
            }
            else {
                $data = yaml_parse_file($from);
            }
        }
        return $data;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief import from a yaml string
    ///
    /// @param $from string
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function fromYamlString($from) {
        if (function_exists('yaml_parse')) {
            return yaml_parse($from);
        }
        return array();
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief import from a ini file
    ///
    /// @param $from file name
    /// @param $args array of setting args
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function fromIniFile($from, $args=array()) {

        // no file
        if (!file_exists($from)) {
            b::log("Unable to read '%s'",array($from));
            return false;
        }

        $data = parse_ini_file($from, true);

        return $data;
    }

    /*
    * @package   Config_Lite
    * @author    Patrick C. Engel <pce@php.net>
    * @copyright 2010-2011 <pce@php.net>
    * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
    */
    public function toIniFile($key=false) {
        $data = ($key ? $this->_bucket->get($key)->asArray() : $this->_bucket->asArray());
        $content = '';
        $sections = '';
        $globals  = '';
        if (!empty($data)) {
            // 2 loops to write `globals' on top, alternative: buffer
            foreach ($data as $section => $item) {
                if (!is_array($item)) {
                    $value    = $item;
                    $globals .= $section . ' = "' . $value .'"'."\n";
                }
            }
            $content .= $globals;
            foreach ($data as $section => $item) {
                if (is_array($item)) {
                    $sections .= "\n[" . $section . "]\n";
                    foreach ($item as $key => $value) {
                        if (is_array($value)) {
                            foreach ($value as $arrkey => $arrvalue) {
                                $arrvalue  = $arrvalue;
                                $arrkey    = $key . '[' . (is_int($arrkey) ? "" : $arrkey) . ']';
                                $sections .= $arrkey . ' = "' . $arrvalue.'"'
                                            ."\n";
                            }
                        } else {
                            $value     = $value;
                            $sections .= $key . ' = "' . $value .'"'."\n";
                        }
                    }
                }
            }
            $content .= $sections;
        }
        return $content;

    }

}