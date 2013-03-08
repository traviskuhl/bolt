<?php

namespace bolt\route;
use \b;

class token extends parser {

    public function match($uri, $method) {

        // loo through each of the paths
        foreach ($this->getPaths() as $path) {

            // break apart our path
            $parts = array();
            $params = array();


            // loop through each part and replace it with a validator
            foreach (explode("/", trim($path, '/')) as $part) {
                if ($part{0} == '{') {
                    $name = trim($part,'{}');
                    $parts[] = '(?P<'.$name.'>'.$this->getValidator($name).')';
                    $params[$name] = false;
                }
                else if (stripos($part, '{') !== false) {
                    if (preg_match_all('#\{([^\}]+)\}#', $part, $matches, PREG_SET_ORDER)) {
                        $i = 0; $rep = array();
                        foreach ($matches as $match) {
                            $part = str_replace($match[0], "~{$i}~", $part);
                            $name = $match[1];
                            $rep[$i++] = '(?P<'.$name.'>'.$this->getValidator($name).')';
                            $params[$name] = false;
                        }
                        $part = preg_quote($part, '#');
                        foreach ($rep as $i => $v) {
                            $part = str_replace("~{$i}~", $v, $part);
                        }
                    }
                    $parts[] = $part;
                }
                else {
                    $parts[] = preg_quote($part, '#');
                }
            }

            // make our regex
            $regex = '#^'.implode($parts,'/').'/?$#';

            // see if we can find something
            if (preg_match_all($regex, $uri, $matches)) {

                // is the method the same
                if ($this->getMethod() != $method AND $this->getMethod() != '*') {
                    return false;
                }

                // match up our params
                foreach ($matches as $name => $match) {
                    if (array_key_exists($name, $params)) {
                        $params[$name] = $match[0];
                    }
                }

                // set our params for later
                $this->setParams($params);

                // yes we found it
                return true;

            }

        }

        // unknown route
        return false;

    }

}