<?php

namespace bolt;
use \b;

class stream {

    private $_file;
    private $_position = 0;

    //
    private function _findFile($path) {
        $url = parse_url($path);

        if (isset($url['host']) AND $url['host'] == 'pear') {
            foreach (explode(PATH_SEPARATOR , get_include_path()) as $dir) {
                $file = b::path($dir, $url['path']);
                if (file_exists($file)) {
                    return $file;
                }
            }
        }
        return $file;
    }


    public function url_stat($path, $flags) {
        $file = $this->_findFile($path);
        return ($file ? stat($file) : false);
    }

    public function stream_open($path, $mode, $options, &$opened_path){
        $file = $this->_findFile($path);

        if (!$file) {return false;}

        $this->_file = fopen($file, $mode);
        $this->_position = 0;

        return true;
    }


    public function stream_read($count){
        return fread($this->_file, $count);
    }

    public function stream_write($data){
        return fwrite($this->_file, $data);
    }

    public function stream_tell(){
        return ftell($this->_file);
    }

    public function stream_eof(){
        return feof($this->_file);
    }

    public function stream_seek($offset, $whence){
        return fseek($this->_file, $offset, $whence);
    }

    public function stream_stat() {
        return fstat($this->_file);
    }

    public function stream_metadata($path, $option, $var) {
        $file = $this->_findFile($path);
        if ($file) {
            switch($option) {
                case STREAM_META_TOUCH:
                    return touch($file);
            };
        }
        return false;
    }
}

stream_wrapper_register("bolt", '\bolt\stream');
