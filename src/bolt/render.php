<?php

namespace bolt;

use \b as b;

// render
b::plug('render', '\bolt\render');

class render extends plugin {

    // factory
    public static $TYPE = 'singleton';

    public function _default($args=array()) {
        if (count($args) > 0) {
            $render = b::render()->call($args['render']);
            return $this->_render($render, $args);
        }
        return $this;
    }


    private function _render($render, $args) {

        $file = p_raw('file', false, $args);
        $string = p_raw('string', false, $args);
        $vars = p_raw('vars', array(), $args);

        // if we have a file, lets try to load it
        if ($file AND stripos($file, '.php') === false) {
            $string = file_get_contents($file);
        }
        else {

            // render in a callback to control scope
            $string = call_user_func(function($_file, $_vars){

                // start the buffer
                ob_start();

                // globalize our variables
                foreach ($_vars as $k => $v) {
                    $$k = (is_object($v) ? $v : b::bucket($v));
                }

                // include our file
                include($_file);

                // content and clean
                $content = ob_get_contents(); ob_clean();

                return $content;

            }, $file, $vars);

        }

        // pass to the render
        return $render->render($string, $vars);

    }

}


