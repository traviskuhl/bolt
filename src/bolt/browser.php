<?php

namespace bolt;
use \b;

// depend on bolt core and plugin browser
b::depend('bolt-core-*')->plug('browser', '\bolt\browser');

class browser extends \bolt\plugin\singleton {




}