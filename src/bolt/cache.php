<?php

namespace bolt;
use \b as b;

// plugin
b::plug('cache', '\bolt\cache');

// source
class cache extends plugin {

    // type is singleton 
    // since this is really a plugin dispatch
    public static $TYPE = "singleton";

}