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
    exit("Unable to run by command line");
}


b::init(array(
    'mode' => 'browser'
));

// find our project config
if (($file = getenv("bProjectConfig")) !== false) {
    b::config()->fromJson($file);
}

// run in cli mode
b::run('browser');
