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
        'symlink' => array(
            'short_name' => '-s',
            'long_name' => '--symlink',
            'description' => 'symlink installed files to current directory',
            'default' => false,
            'action' => 'StoreFalse'
        )
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
        $build = json_decode(file_get_contents("./build.json"), true);

//        print_r($build); die;

        $rel = realpath(getcwd());

        // shortcuts
        $dirs = (isset($build['build']['dir']) ? $build['build']['dir'] : array());
        $files = (isset($build['build']['file']) ? $build['build']['file'] : array());

        // loop through all directories in the build
        // manifest and make sure theyre roots are created
        //  dir[0] path
        //  dir[1] perm
        //  dir[2] user
        //  dir[3] group
        foreach ($dirs as $dir) {
            $base = dirname($dir[0]);

            if (!is_dir($base)) {

                if (!isset($dir[1])) {
                    $dir[1] = 0755;
                }

                // mkdir
                mkdir($base, 0755, true);

                // stuff
                if (isset($dir[1])) {
                    chmod($base, $dir[1]);
                }
                if (isset($dir[2])) {
                    chown($base, $dir[2]);
                }
                if (isset($dir[3])) {
                    chgrp($base, $dir[3]);
                }

            }
        }

        $sym = true;

        // now loop through again and
        // see what we need to move over
        foreach ($dirs as $dir) {
            $dest = $dir[0];
            $base = dirname($dest);
            $root = basename($dest);
            $src = b::path($rel, $dest);

            // if symlink
            if ($sym) {

                // move
                chdir($base);

                if (file_exists($root) AND !is_link($root)) {
                    return $this->fail("Trying to symlink a hardlinked directory ($root => $src). use `--force` to remove old directory.");
                }
                else if (file_exists($root)) {
                    unlink($root);
                }

                // symlink back
                $r = symlink($src, $root);

            }
            else {

                // remove it if it exists
                if (file_exists($dest)) { `rm -r $dest`; } // this is hack. we need something better

                rename($src, $dest);

            }

        }

        // now files
        // they don't get symlinked, they just get
        // overritten
        // file[0] = dest
        // file[1] = src
        foreach ($files as $file) {
            $src = b::path($rel, $file[0], basename($file[1]));
            $root = basename($src);
            $dest = b::path($file[0], $root);
            $base = dirname($dest);

            if (!is_dir($base)) {
                mkdir($base, $file[2]['perm'], true);
            }

            if (isset($file[2]['user'])) {
                chown($src, $file[2]['user']);
            }
            if (isset($file[2]['group'])) {
                chown($src, $file[2]['user']);
            }

            // if symlink
            if ($sym) {

                // move
                chdir($base);

                if (file_exists($root) AND !is_link($root)) {
                    return $this->fail("Trying to symlink a hardlinked file ($root => $src). use `--force` to remove old file.");
                }
                else if (file_exists($root)) {
                    unlink($root);
                }

                echo "$root => $src\n";

                // symlink back
                $r = symlink($src, $root);

            }
            else {

                // remove it if it exists
                if (file_exists($dest)) { unlink($dest); }

                // link
                rename($src, $dest);

            }

        }

        // packageDir
        $packageDir = b::path(b::client()->getVarDir(), $build['name']);

        // make sure our var dir exists
        if (!is_dir($packageDir)) {
            mkdir($packageDir, 0755, true);
        }

        // place our package file
        rename("$rel/package.json", "$packageDir/package.json");


        return $this->done("Install Complete");


        // // awesome, no loop through each and move some shit
        // foreach (new \RecursiveDirectoryIterator(".", \FilesystemIterator::SKIP_DOTS) as $dir) {
        //     if ($dir->isFile()) {continue; }

        //     // get all files in the dir
        //     foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
        //         $src = realpath($file->getPathname());
        //         $dest = b::path(str_replace($rel, "", $file->getRealPath()));
        //         $base = dirname($dest);

        //         // make sure the dest dir exists
        //         // and copy the src perms
        //         if (!file_exists($base)) {mkdir($base, 1666, true); }

        //         // remove it if it exists
        //         if (file_exists($dest)) { unlink($dest); }

        //         // do it
        //         rename($src, $dest);

        //     }

        // }

    }

}