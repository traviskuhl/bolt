<?php

namespace bolt\client;
use \b;

// we need tar
require_once "Archive/Tar.php";
require_once "File/Find.php";

use \File_Find;
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
        $this->_tmp = "/tmp/bolt-build-test"; @mkdir($this->_tmp); chmod($this->_tmp, 0777);

        // name
        $this->_name = $this->_pkg['name'];

        // config
        $config = array_merge(array(
                'src' => $this->_pkg,
                'directories' => array()
            ), $this->_pkg);

        // build
        $this->_build = b::param('build', false, $this->_pkg);

        // no build
        if ($this->_build === false) {
            return $this->fail("No build information found in package");
        }

        // do they want us to compile first?
        if (isset($this->_build['compile']) AND $this->_build['compile'] == true) {

            // compile
            $c = new \bolt\client\compile();

            // do it
            $c->execute(getcwd());

        }

        // first create all of our directories
        if (isset($this->_build['dir'])) {
            foreach ($this->_build['dir'] as $dir) {
                @mkdir(b::path($this->_tmp, $dir), 0644, true);
                @chmod(b::path($this->_tmp, $dir), 0644);
            }
        }

        // find
        if (isset($this->_build['find'])) {
            foreach ($this->_build['find'] as $find) {

                // destinaton
                $dest = b::path($this->_tmp, $find[0]);

                // relative to
                $rel = realpath($find[1]);

                // find the files
                $files = File_Find::search($find[2], $rel, 'shell', false);

                // copy files into our tmp
                foreach ($files as $src) {
                    $file_dest = str_replace($rel, $dest, $src);
                    $base = dirname($file_dest);

                    if (!is_dir($base)) {
                        @mkdir($base, 0644, true);
                        @chmod($base, 0644);
                    }

                    copy($src, $file_dest);
                    chmod($file_dest, substr(sprintf('%o', fileperms($src)), -4));

                }
            }
        }

        // find
        if (isset($this->_build['file'])) {
            foreach ($this->_build['file'] as $file) {
                if (!isset($file[2])) {
                    $this->_build['file'][2] = array('perm' => 1644);
                    $file[2] = array(
                        'perm' => 1644
                    );
                }


                // destinaton
                $dest = b::path($this->_tmp, $find[0]);

                // relative to
                $rel = realpath($find[1]);

                $file_dest = str_replace($rel, $dest, $src);
                $base = dirname($file_dest);

                if (!is_dir($base)) {
                    @mkdir($base, $file[2]['perm'], true);
                    @chmod($base, $file[2]['perm']);
                }

                copy($src, $file_dest);
                chmod($file_dest, $file[2]['perm']);

            }
        }

        // settings we can localize if they're not already
        if (isset($this->_pkg['settings']) AND is_string($this->_pkg['settings'])) {
            $this->_pkg['settings'] = b::settings()->importFile($this->_pkg['settings'], $this->_root)->asArray();
        }

        // config should reset for build
        if (isset($this->_build['config']) AND is_array($this->_build['config'])) {
            $this->_pkg['config'] = array_merge($this->_build['config'], $this->_pkg['config']);
        }

        // files are overritten
        if (isset($this->_build['files']) AND is_array($this->_build['files'])) {
            $this->_pkg['files'] = $this->_build['files'];
        }

        // directories are overritten
        if (isset($this->_build['directories']) AND is_array($this->_build['directories'])) {
            $this->_pkg['directories'] = $this->_build['directories'];
        }

        // use git to get the version
        $git = $this->exec('git log --pretty="%h|%ci|%ct" -n1');

        // figure out our version
        list($sha, $date, $ts) = explode("|", array_shift($git));

        $config['version'] = implode('-',array($this->_pkg['version'], $sha));

        // package
        $outPackage = b::path($this->_tmp, b::client()->getVarDir(), $this->_pkg['name'], 'package.json'); @mkdir(dirname($outPackage), 777, true);

        // export our package
        file_put_contents($outPackage, json_encode($this->_pkg));

        // output our config
        file_put_contents(b::path($this->_tmp, "build.json"), json_encode($config));

        // name for package
        $name = str_replace("/", "-", $this->_name);

        // tar name
        $tarName = "{$pwd}/{$name}-{$config['version']}.tar.gz";

        // create our tar
        $tar = new \Archive_Tar($tarName, 'gz');

        // move into tmp
        chdir($this->_tmp);

        // create our tar
        $tar->create(array('.'));

        // move back to pwd
        chdir($pwd);

        // remove temp
        $cmd = "sudo rm -r {$this->_tmp}"; `$cmd`;

        // give back to the user
        @chown($tarName, b::client()->getUser());
        @chgrp($tarName, b::client()->getUser());

        // done
        $this->done("Build Complete {$name}-{$config['version']}");

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