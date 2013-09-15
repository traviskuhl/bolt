<?php

namespace bolt\client\compile;
use \b;

use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;

class views extends base {

    // name of file
    const NAME = 'views';

    // compile
    public static function compile($pkg) {

        // views
        $dirs = $pkg->getDirectories('views');

        // compile templates
        $views = array();

        // render
        $render = b::render();

        foreach ($dirs as $dir) {

            // add our files
            foreach (new RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $item) {
                if (!$item->isFile()) {continue;}

                // ext
                $ext = $item->getExtension();

                // relative path
                $rel = str_replace($dir, "", $item->getPathname());

                // compiled
                $data = $render->getRenderer($ext)->compile(file_get_contents($item->getPathname()));

                // get the file
                if ($data) {
                    $views[$rel] = new \bolt\render\compiled($rel, $data);
                }

            }

        }

        return array(
            'data' => $views
        );

    }

}