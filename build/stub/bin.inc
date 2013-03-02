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

// include bolt
require("%PHAR%");

// if we're in cli, load it
// otherwise we're just including the phar
if ('cli' === php_sapi_name()) {

    // init our bolt instance
    b::init(array(
        'mode' => 'cli',
        'load' => array(
            bRoot."/bolt/client/*.php"
        )
    ));

    // get our settings
    $home = p('SUDO_HOME', p('HOME', false, $_SERVER), $_SERVER);
    $user = p('SUDO_USER', p('USER', false, $_SERVER), $_SERVER);

    // home
    if ($home === false) {
        $home = "/home/{$user}";
    }
    if (!file_exists("{$home}/.bolt")) {
        mkdir("{$home}/.bolt/");
    }

    // settings
    b::setSettings('client', "{$home}/.bolt/settings.json");

    // when we're done, save settings
    b::on('destruct', function(){
        b::getSettings('client')->save();
    });

    // run
    b::run();

}
else {
    exit("Unable to run!");
}