#!/usr/bin/env php
<?php
///////////////////////////////////////////////////////////////////////
/// (c) the.kuhl.co 2012 - author: travis kuhl (travis@kuhl.co)
///
/// Licensed under the Apache License, Version 2.0 (the "License");
/// you may not use this work except in  compliance with the License.
/// You may obtain a copy of the License in the LICENSE file, or at:
///
/// http://www.apache.org/licenses/LICENSE-2.0
///
/// Unless required by applicable law or agreed to in writing,
/// software distributed under the License is distributed on an "AS IS"
/// BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
/// express or implied. See the License for the specific language
/// governing permissions and limitations under the License.
///////////////////////////////////////////////////////////////////////
/// Build: %SHA% / %DATE%
/// Compiled: %CDATE%


// we need to be in cli mode for this to work
if ('cli' === php_sapi_name()) {

    // install
    if (file_exists("bolt-install.php") OR (count($argv) > 1 AND $argv[1] == 'install')) {
        include 'bolt-install.php';
        exit;
    }

    // include bolt
    require("bolt.phar");

    // init our bolt instance
    b::init(array(
        'mode' => 'cli',
        'load' => array(
            bRoot."/bolt/client.php",
            bRoot."/bolt/client/*.php"
        )
    ));

    // start a bolt client
    b::client()->start();

    // run
    b::run();

}
else {
    exit("Unable to run!");
}