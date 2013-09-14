<?php

namespace bolt\browser\route;
use \b;

class token extends parser {


    private $_tokens = array();

    const SEP = '/.*,;:-_~+=@|';

    public function compile() {

        // loo through each of the paths
        $path = $this->getPath();
        $resp = $this->getResponse();

        if ($resp) {
            $path .= '.{_b_response}';
            $this->validate('_b_response', implode('|', $resp));
            $this->optional('_b_response');
        }

        // nothin to match
        if (stripos($path, '{') === false) {
            $regex = $path;
        }
        else {

            // match all
            preg_match_all('#\{\w+\}#', $path, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

            // params holder
            $pos = 0;
            $regex = "";

            foreach ($matches as $match) {
                $token = $match[0][0];
                $off = $match[0][1];

                $name = substr($token, 1, -1);
                $this->_tokens[$name] = false;

                // where in the patther
                $before = substr($path, $pos, $off - $pos);
                $pos = $off + strlen($token);

                // char
                $charBefore = strlen($before) > 0 ? substr($before, -1) : '';
                $charBeforeSep = (!empty($charBefore) && stripos(self::SEP, $charBefore) !== false);

                $regex .= preg_quote($before);

                // figure out the regex for this replace
                if (!$this->hasValidator($name)) {
                    $after = preg_replace('#\{\w+\}#', '', substr($path, $pos));
                    $sep = '[^'.((strlen($after) > 0  AND stripos(self::SEP, $after[0])) ? preg_quote($after[0]) : '/').']+';
                    $regex .= ($this->isOptional($name) ? '?(?P<'.$name.'>'.$sep.')?' : '(?P<'.$name.'>'.$sep.')');
                }
                else {
                    $regex .= ($this->isOptional($name) ? '?(?P<'.$name.'>'.$this->getValidator($name).')?' : '(?P<'.$name.'>'.$this->getValidator($name).')');
                }

            }
        }

        $regex = '/'.trim($regex, '/');
        return $this->_compiled = '#^'.$regex.($resp ? '$' : '/?$').'#';

    }

    public function match($uri) {

        if (!$this->_compiled) {
            $this->compile();
        }

        $params = $this->_tokens;


        // see if we can find something
        if (preg_match_all($this->_compiled, $uri, $matches)) {

            // match up our params
            foreach ($matches as $name => $match) {
                if ($name === '_b_response' AND !empty($match[0])) {
                    $this->setResponseType($match[0]);
                }
                else if (array_key_exists($name, $params)) {
                    $params[$name] = $match[0];
                }
            }

            // set our params for later
            $this->setParams($params);

            // yes we found it
            return true;

        }


        // unknown route
        return false;

    }

}