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

    ////////////////////////////////////////////////////////////////////
    /// @brief import from a jason string or file
    ///
    /// @param $from string or file to get from
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function fromJson($from) {
        if (is_resource($from)) {
            $str = $buffer = false;
            while (($buffer = fgets($from, 4096)) !== false) {
                $str .= $buffer;
            }
            fclose($from);
            $this->set(json_decode($str, true));
        }
        else if ($from{0} == '{' OR $from{0} == '[') {
            $this->set(json_decode($from, true));
        }
        else if (file_exists($from)) {
            $this->set(json_decode(file_get_contents($from), true));
        }
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief import from a yaml file
    ///
    /// @param $from file to get from
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function fromYamlFile($from) {
        if (function_exists('yaml_parse_file')) {
            if (is_resource($from)) {
                $str = $buffer = false;
                while (($buffer = fgets($from, 4096)) !== false) {
                    $str .= $buffer;
                }
                fclose($from);
                $this->set(yaml_parse($str));
            }
            else {
                $this->set(yaml_parse_file($from));
            }
        }
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief import from a yaml string
    ///
    /// @param $from string
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function fromYamlString($from) {
        if (function_exists('yaml_parse')) {
            $this->set(yaml_parse($from));
        }
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief import from a ini file
    ///
    /// @param $from file name
    /// @param $args array of setting args
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function fromIniFile($from, $args=array()) {

        // cache name
        $cid = md5("config.ini.$from");
        $data = false;

        // cache
        if (p('cache', false, $args) == 'apc') {
            $data = apc_fetch($cid);
        }

        // no data
        if (!$data) {
            $data = parse_ini_file($from, true);
        }

        if (p('cache', false, $args) == 'apc') {
            apc_store($cid, $data, p('ttl', false, $args));
        }

        if (p('key', false, $args)) {
            $this->set($args['key'], $data);
        }
        else {
            $this->set($data);
        }

        return $this;
    }


}