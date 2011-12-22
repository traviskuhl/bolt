<?php

namespace bolt;
use \b as b;

// plugin
b::plug('source', '\bolt\source');

// source
class source extends plugin {

    // type is singleton 
    // since this is really a plugin dispatch
    public static $TYPE = "singleton";

}