<?php

namespace bolt\client;
use \b;


// class bundle{


//     public function install($plugin) {

//         //  tar
//         $tar = false;

//         // plugin is a file
//         if (file_exists($plugin)) {
//             $tar = file_get_contents($plugin);
//         }
//         // plugin is a url
//         else if (filter_var($plugin, FILTER_VALIDATE_URL)) {
//             $tar = b::webservice()->request($plugin)->body();
//         }
//         // fetch from dist
//         else {
//             exit('comming soon');
//         }

//         var_dump($tar); die;

//     }

//     public function compile($dir=false) {
//         $pwd = getcwd();
//         $dir = ($dir ?: getcwd());

//         // move into dir
//         chdir($dir);

//         // root
//         $pkg = $dir."/package.json";

//         // no package file
//         if (!file_exists($pkg)) {
//             $this->err("No package file in %s", $pkg);
//         }

//         // open the package
//         $package = json_decode(file_get_contents($pkg), true);

//         // make sure name doesn't have anything crazy
//         if (preg_match("#[^a-zA-Z0-9\/]+#", $package['name'])) {
//             $this->error("Package name '%s' is invalid", $package['name']);
//         }

//         // get our version
//         list($sha, $date, $ts) = explode("|", trim(`git log --pretty="%h|%ci|%ct" -n1 `));

//         // version
//         $version = p('version', $sha, $package);

//         // filename
//         $filename = str_replace('/', '-', $package['name'])."-{$version}.tar.gz";

//         // create a
//         $tar = new Archive_Tar($filename, 'gz');

//         // version
//         $version = $sha;

//         // lets get our files
//         $dir = new RecursiveDirectoryIterator('../src/');
//         $it = new RecursiveIteratorIterator($dir);
//         $regex = new RegexIterator($it, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

//         // add our files
//         foreach (iterator_to_array($regex) as $file) {

//             // path - ../lib
//             $path = str_replace("../src/", "", $file[0]);

//             // drop some white space
//             $content = stripWhitespace(file_get_contents($file[0]));

//             // version
//             $content = preg_replace("/const VERSION = '.*?';/", "const VERSION = '".$version."';", $content);

//             // add it
//             $phar->addFromString($path, $content);

//         }


//         var_dump($package); die;

//     }

// }