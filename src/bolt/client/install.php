<?php

namespace bolt\client;
use \b;

// we need tar
require_once "Archive/Tar.php";

use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use \RecursiveRegexIterator;
use \RegexIterator;
use \Archive_Tar;

class install extends \bolt\cli\command {

    public static $name = 'install';
    public static $options = array(

    );

    private $_root;
    private $_pkg;
    private $_tmp;
    private $_name;
    private $_build;

    public function execute($file=".") {
        $pwd = getcwd();

        // is file a dir
        if (is_dir($file)) {
            $tar = glob(realpath($file)."/*.tar.gz");
            $file = array_shift($tar);
        }

        // not a file
        if (!file_exists($file)) {
            return $this->fail("Unable to locate build file.");
        }

        // tmp
        $this->tmp = b::client()->getTmp();

        // copy our folder
        copy($file, "{$this->tmp}/package.tar.gz");


        // move into tmp
        chdir($this->tmp);

        // extract our tar into build
        $tar = new Archive_Tar("package.tar.gz");
        $tar->extract();

        // ok lets loop through and create our directories
        $config = json_decode("build.json");

        $rel = realpath(getcwd());

        // awesome, no loop through each and move some shit
        foreach (new \RecursiveDirectoryIterator(".", \FilesystemIterator::SKIP_DOTS) as $dir) {
            if ($dir->isFile()) {continue; }

            // get all files in the dir
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
                $src = realpath($file->getPathname());
                $dest = b::path(str_replace($rel, "", $file->getRealPath()));
                $base = dirname($dest);

                // make sure the dest dir exists
                // and copy the src perms
                if (!file_exists($base)) {mkdir($base, 0644, true); }

                // remove it if it exists
                if (file_exists($dest)) { unlink($dest); }

                // do it
                rename($src, $dest);

            }

        }

    }

}