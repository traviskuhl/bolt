<?php

// namespace
namespace bolt;

// plugin
\b::plug('model', '\bolt\model');


class model extends plugin\factory {

    static $_shortcuts = array();
    static $_traits = array();

    ////////////////////////////////////////////////////////////
    /// @brief factory class for dao
    ///
    /// @params $args arguments passed to __construct
    /// @return new dao
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

            if (count($args) > 0) {
                $ref = new \ReflectionClass($class);
                return $ref->newInstanceArgs($args[0]);
            }
            else {
                return new $class();
            }


        }

        // return a default object
        return false;

    }

    ////////////////////////////////////////////////////////////
    /// @brief add a shortcut
    ///
    /// @param $name name of shortcut
    /// @param $class dao class
    /// @return void
    ////////////////////////////////////////////////////////////
    public static function shortcut($name, $class) {
        self::$_shortcuts[$name] = $class;
    }

    ////////////////////////////////////////////////////////////
    /// @brief  add a gobal trait
    ///
    /// @param $class trait class
    /// @return void
    ////////////////////////////////////////////////////////////
    public static function traits($class) {
        self::$_traits[] = $class;
    }

    ////////////////////////////////////////////////////////////
    /// @brief return a list of shortcust
    ///
    /// @return array of shortcuts
    ////////////////////////////////////////////////////////////
    public static function getShortcuts() {
        return self::$_shortcuts;
    }

    ////////////////////////////////////////////////////////////
    /// @brief return a list of traits
    ///
    /// @return array of traits
    ////////////////////////////////////////////////////////////
    public static function getTraits() {
        return self::$_traits;
    }

}
