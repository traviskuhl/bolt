<?php

namespace bolt;
use \b;

interface iBucket {

}

// bucket
b::plug('bucket', '\bolt\bucket');

class bucket extends \bolt\plugin\factory {

    /**
     * generate a bucket object
     *
     * @param $data list of arguments passed to factory
     * @see self::byType
     * @return mixed bucket object
     */
    public static function factory($data=array()) {
        return self::byType($data);
    }

    static function __callStatic($name, $args) {
        return call_user_func_array(array(self, $name), $args);
    }

    /**
     * is the provided object a bucket
     *
     * @param $obj object to test
     * @return bool if it is
     */
    static function isBucket($obj) {
        return b::isInterfaceOf($obj, '\bolt\iBucket');
    }

    /**
     * generate bucket object by type
     *
     * @param $value mixed data
     * @param $key name of key to pass to object
     * @param $parent object
     * @return mixed object or false
     */
    static function byType($value, $key=false, $parent=false) {

        // if we're already an object
        if (b::isInterfaceOf($value, '\bolt\iBucket')) {
            return $value;
        }

        // what type
        switch(gettype($value)) {

            // stringish
            case 'boolean':
            case 'integer':
            case 'double':
            case 'float':
            case 'string':
            case 'NULL':
                return new bucket\bString($value, $key, $parent); break;

            // object
            case 'object':
                return new bucket\bObject($value, $key, $parent); break;

            // array
            case 'array':
                return new bucket\bArray($value, $key, $parent); break;

            default:
                return $value;
        };
    }


}