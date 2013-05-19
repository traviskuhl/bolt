<?php

// version
$version = "%VERSION%";

echo "\n";
echo str_repeat("-", 30);
echo "Bolt Install\n";
echo str_repeat("-", 30);
echo "\nThis your first time using bolt. We need\n";
echo "setup a few things. Just hold tight...\n";
echo str_repeat("-", 30);
echo "\nAND GO!...\n";

// open dependency file
$depend = json_decode('%DEPEND%', true);

// tmp
$tmp = sys_get_temp_dir()."/".time();
mkdir($tmp);

// we're in pear
$pear = __DIR__;
mkdir("$pear/bolt");
mkdir("$pear/bolt/vendor");

// loop through
foreach ($depend as $pkg) {
    // get it and palce it in tmp
    $cmd = "git clone {$pkg['repo']['uri']} -b {$pkg['repo']['branch']} $tmp/{$pkg['name']}";
    echo `$cmd`;
    $cmd = "sudo mv -f $tmp/{$pkg['name']}/{$pkg['lib']} $pear/bolt/vendor";
    echo `$cmd`;
}

// remove ourselves
unlink(__FILE__);

// done
echo str_repeat("-", 30);
echo "\nInstall Complete!!\n";
echo "You can start using bolt now.\n";
