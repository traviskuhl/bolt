<?php

namespace bolt\model;
use \b;

abstract class attr  {

    abstract public function set($value);
    abstract public function get($value);
    abstract public function normalize($value);

}