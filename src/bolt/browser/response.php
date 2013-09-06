<?php

namespace bolt\browser;
use \b;

interface iResponse {

}

/**
 * browser response
 * @extends \bolt\plugin
 *
 */
class response {

    static $responseTypes = false;

    public static function getResponseTypes() {
        if (!is_array(self::$responseTypes)) {
            foreach (b::getDefinedSubClasses('\bolt\browser\response\base') as $obj) {
                if ($obj->name !== 'bolt\browser\response') {
                    self::$responseTypes[$obj->getConstant('TYPE')] = $obj->name;
                }
            }
        }
        return self::$responseTypes;
    }

    public static function initByType($type) {
        $types = self::getResponseTypes();

        // is this type in types
        if (array_key_exists($type, $types)) {
            return new $types[$type];
        }

        // return a base
        return new response\base();

    }

}
