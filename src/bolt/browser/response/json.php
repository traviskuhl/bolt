<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('json', '\bolt\browser\response\json');

// json
class json extends \bolt\plugin\singleton {

    // accept or header
    public static $accept = array(
        100 => 'text/javascript',
        200 => 'text/javascript;secure'
    );

    // content type
    public $contentType = "text/javascript";

    //
    public function getContent($view) {

        $resp = json_encode(array(
            'status' => b::response()->getStatus(),
            'response' => $view->getContent()
        ));

        // pretty
        $resp = (p('_pretty') ? $this->_pretty($resp) : $resp);

        // securet
        if ($view->getAccept() == 'text/javascript;secure') {
            $resp = 'while(1);'.$resp;
        }

        // give it up
        return $resp;

    }

    private function _pretty($json) {
        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '  ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i=0; $i<=$strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

            // If this character is the end of an element,
            // output a new line and indent the next line.
            } else if(($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;

    }


}