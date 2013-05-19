<?php

// version
$version = "%VERSION%";

echo "\n";
echo str_repeat("-", 50);
echo "\n Bolt Install\n";
echo str_repeat("-", 50);
echo "\nThis your first time using bolt. We need to\n";
echo "setup a few things. Just hold tight...\n\n";
echo str_repeat("-", 50);

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
unlink($tmp);

// done
echo "\n";
echo str_repeat("-", 50);
echo "\n\nInstall Complete!!\n";
echo "You can start using bolt now.\n";
echo "./bolt start\n\n";
exit;