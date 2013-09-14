<?php

namespace bolt\client;
use \b;


class compile extends \bolt\cli\command {

    public static $name = 'compile';
    public static $options = array(

    );

    private $_out = false;
    private $_pkg = false;

    public function execute($src=".", $out="./compiled") {
        $pwd = getcwd();

        // move to root
        chdir(realpath($src));

        $this->_root = getcwd();

        //  package file
        $this->_pkg = new \bolt\package("./package.json");

        b::package($this->_pkg);

        // create our build directory
        if (!file_exists("$out")) {
            mkdir($out, 0777, true);
        }

        // OUT
        $this->_out = realpath($out);

        // we need bolt browser
        // to compile our routes
        b::depend("bolt-browser-*");

        // load the root
        if ($this->_pkg->getDirectories('pear')) {
            b::load($this->_pkg->getDirectories('pear'));
        }
        if ($this->_pkg->getDirectories('load')) {
            b::load($this->_pkg->getDirectories('load'));
        }

        // get all compile classes
        $compilers = b::getDefinedSubClasses('\bolt\client\compile\base');

        $config = array(
                'files' => array()
            );

        // doooooo it
        foreach ($compilers as $class) {
            $resp = call_user_func(array($class->name, 'compile'), $this->_pkg);

            // we have a resp
            if (is_array($resp) AND $resp) {

                $name = $class->getConstant("NAME");

                // data to write to a file
                if (isset($resp['data'])) {
                    $this->_writeCompiledFile($name, $resp['data']);
                    $config['files'][$name] = "{$this->_out}/{$name}.inc";
                }

                if (isset($resp['files'])) {
                    foreach ($resp['files'] as $fname => $data) {
                        $this->_writeFile(b::path($name, $fname), $data);
                    }
                }

                if (isset($resp['config'])) {
                    $config[$name] = $resp['config'];
                }

            }

        }

        // done!
        chdir($pwd);

        return $config;

    }

    private function _writeFile($name, $data) {
        $file = b::path($this->_out, $name);
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        file_put_contents($file, $data);
        chmod($file, 0777);
    }

    private function _writeCompiledFile($name, $data) {
        $this->_writeFile("{$name}.inc", '<?php return '.var_export($data, true).'; ?>');
    }

}