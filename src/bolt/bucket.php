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
     * @param $args list of arguments passed to factory
     *               $args[0] mixed data
     *               $args[1] key name
     *               $args[2] parent of data
     * @return mixed bucket object
     */
    public static function factory($args=array(), $key=null, $parent=null) {
        if ($key !== null OR $parent !== null) {
            $data = $args;
        }
        else {
            $data = (isset($args[0]) ? $args[0] : array());
            $key = (isset($args[1]) ? $args[1] : false);
            $parent = (isset($args[2]) ? $args[2] : false);
        }

        // pass to by type
        return self::byType($data, $key, $parent);

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