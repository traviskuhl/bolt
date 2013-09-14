<?php

namespace bolt\client;
use \b;

// we need tar
require "Archive/Tar.php";

use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use \RecursiveRegexIterator;
use \RegexIterator;

class build extends \bolt\cli\command {

    public static $name = 'build';
    public static $options = array(

    );

    private $_root;
    private $_pkg;
    private $_tmp;
    private $_name;
    private $_build;

    public function execute($src=".") {
        $pwd = getcwd();

        // move to root
        chdir(realpath($src));

        // no config file
        if (!file_exists("package.json")) {
            return $this->fail("No config.ini or package.json file found.");
        }

        $this->_root = getcwd();

        //  package file
        $this->_pkg = json_decode(file_get_contents("package.json"),true);

        // create our temp directory
        $this->_tmp = "/tmp/bolt-build-test"; @mkdir($this->_tmp);

        // name
        $this->_name = $this->_pkg['name'];

        // config
        $config = array_merge(array(
                'src' => $this->_pkg,
                'directories' => array()
            ), $this->_pkg);

        // build
        $this->_build = $this->_pkg['build'];

        // do they want us to compile first?
        if (isset($this->_build['compile']) AND $this->_build['compile'] == true) {

            // compile
            $c = new \bolt\client\compile();

            // do it
            $c->execute(getcwd());

            // add compiled folder to var
            $this->_pkg['directories']['var'][] = 'compiled/';

            // add to config
            $config['directories']['compiled'] = b::path($this->_build['dest']['var'], $this->_name, 'compiled');

        }

        // build pear folder
        if (isset($this->_pkg['directories']['pear'])) {
            $this->_buildPkgFolder('pear', '.+\.php', $this->_pkg['directories']['pear']);
            $config['directories']['pear'] = b::path($this->_build['dest']['pear'], $this->_name);
        }

        // build htdocs folder
        if (isset($this->_pkg['directories']['htdocs'])) {
            $this->_buildPkgFolder('htdocs', '[^\.].+', $this->_pkg['directories']['htdocs']);
            $config['directories']['htdocs'] = b::path($this->_build['dest']['htdocs'], $this->_name);
        }

        // build variable folder
        if (isset($this->_pkg['directories']['var'])) {
            $this->_buildPkgFolder('var', '[^\.].+', $this->_pkg['directories']['var']);
            $config['directories']['var'] = b::path($this->_build['dest']['var'], $this->_name);
        }

        // use git to get the version
        $git = $this->exec('git log --pretty="%h|%ci|%ct" -n1');

        // figure out our version
        list($sha, $date, $ts) = explode("|", array_shift($git));

        $config['version'] = implode('-',array($this->_pkg['version'], $sha));

        print_r($config); die;

        // export our config
        file_put_contents("{$this->_tmp}/config.json", json_encode($config));

        $name = str_replace("/", "-", $this->_name);

        // create our tar
        $tar = new \Archive_Tar("{$pwd}/{$name}-{$config['version']}.tar.gz", 'gz');

        // move into tmp
        chdir($this->_tmp);

        // create our tar
        $tar->create(array('.'));

        // move back to pwd
        chdir($pwd);

        // remove temp
        unlink($this->_tmp);

        // done
        $this->done("Build Complete {$name}-{$config['version']}");

    }

    public function _buildPkgFolder($dest, $regex, $dirs) {
        $root = "";
        if (is_array($dirs[0]) AND key($dirs[0]) == 'root') {
            $root = $dirs[0]['root'];
            unset($dirs[0]);
        }

        // move
        $copy = array();

        foreach ($dirs as $item) {
            list($full, $rel) = $this->_getFilePath(b::path($root,$item));

            if (is_dir($full)) {
                $copy = array_merge($copy, $this->_getFilesByRegex($full, $regex));
            }
            else if (is_file($full)) {
                $copy[$full] = $rel;
            }
        }

        // move into tmp
        $this->_copyIntoTmp(b::path($dest, $this->_name), $copy, $root);

    }




    private function _copyIntoTmp($dir, $copy, $root="") {
        $dir = b::path($this->_tmp, $dir);
        if (!file_exists($dir)) {
            mkdir($dir, 0644, true);
        }

        // move them
        foreach ($copy as $src => $dest) {
            $dest = b::path($dir, str_replace($root, "", $dest));
            $base = dirname($dest);
            if (!is_dir($base)) { @mkdir($base, 0644, true); }
            copy($src, $dest);
        }

        return true;

    }

    private function _getFilesByRegex($dir, $regex) {
        if (!is_dir($dir)){ return array();}

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        $regex = new RegexIterator($it, '/^'.$regex.'$/i', RecursiveRegexIterator::GET_MATCH);

        $files = array();

        // add our files
        foreach (iterator_to_array($regex) as $file) {
            if (is_dir($file[0])) {continue;}
            $files[$file[0]] = str_replace($this->_root, "", $file[0]);
        }

        return $files;

    }

    private function _mkdir($dir) {
        @mkdir("{$this->_tmp}/{$dir}", null, true);
        return b::path($this->_tmp, $dir);
    }

    private function _getFilePath($item) {
        return array(realpath("{$this->_root}/$item"), str_replace($this->_root, "", realpath("{$this->_root}/$item")));
    }

}