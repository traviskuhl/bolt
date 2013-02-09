<?php

// namespace
namespace bolt;

// plugin
\b::plug('dao', '\bolt\dao');


////////////////////////////////////////////////////////////
/// @brief dao implentation
////////////////////////////////////////////////////////////
class dao extends plugin\factory {

    static $_shortcuts = array();
    static $_traits = array();

    ////////////////////////////////////////////////////////////
    /// @brief factory
    ////////////////////////////////////////////////////////////
    public static function factory($args = array()) {

        // args
        $args = func_get_args();

        // the first part of args should be
        // the class name
        $class = array_shift($args);

        // see if it's in a short
        if (array_key_exists($class, self::$_shortcuts)) {
            $class = self::$_shortcuts[$class];
        }

        // try to load this class
        if (class_exists($class, true)) {

            // we've got the class
            // let's create an object
            return new $class($args);

        }

        // return a default object
        return new bucket();

    }

    public static function shortcut($name, $class) {
        self::$_shortcuts[$name] = $class;
    }
    public static function trait($class) {
        self::$_traits[] = $class;
    }

}
