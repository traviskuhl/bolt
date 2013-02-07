<?php

namespace bolt\render;
use \b;


// render
b::render()->plug('markdown', '\bolt\render\markdown');

// markdown
class markdown extends \bolt\plugin\singleton {

    private $eng;

    public function __construct() {

        // include
        require_once bRoot.'/vendor/markdown/markdown.php';

    }

    public function render($str, $vars=array()) {
      return \Markdown($str);
    }

}
