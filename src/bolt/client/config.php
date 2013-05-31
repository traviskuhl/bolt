<?php

namespace bolt\client;
use \b;


// class config extends \bolt\cli\command {

//     public static $commands = array(
//         'set' => array(
//             'arguments' => array(
//                 'vars' => array('multiple' => true)
//             )
//         )
//     );

//     public function run() {


//         var_dump(func_get_args()); die;

//         // get config data
//         $config = b::config()->asArray();

//         $this->out(b::jsonPretty($config));

//     }


//     public function set($vars) {

//         var_dump(func_get_args()); die;

//         if (count($this->getArgv()) == 0) {
//             return $this->list();
//         }

//         list($file, $storage) = $this->getStorage();

//     }

// }