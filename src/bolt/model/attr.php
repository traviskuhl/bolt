<?php

namespace bolt\model;
use \b;

abstract class attr  {

    abstract public function set($value);
    abstract public function get();
    abstract public function normalize();
    abstract public function value();

}