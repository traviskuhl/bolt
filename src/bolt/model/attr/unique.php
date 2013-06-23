<?php

namespace bolt\model\attr;
use \b;

class unique extends \bolt\model\attr\base {

    public function normalize($value) {
        if (empty($value)) {
            return uniqid();
        }
        return $value;
    }

}