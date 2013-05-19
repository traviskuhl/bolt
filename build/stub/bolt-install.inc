<?php

// version
$version = "%VERSION%";

// open dependency file
$depend = json_decode("%DEPEND%", true);

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
echo "Bolt Install Complete\n\n";
echo "You can start using bolt now.\n";
exit;