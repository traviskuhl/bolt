<?php

namespace bolt\render;
use \b;

class handlebars extends base {

    public static $extension = array('hbr');

    private $_eng = false;

    public function __construct() {

        $this->_eng = new \Handlebars_Engine(array(
            'delimiter' => "<% %>",
            'escape' => function($value) {
                return htmlentities((string)$value, ENT_QUOTES, 'UTF-8', false);
            }
        ));

        // add our default helpers
        $this->_addHelpers();
    }

    public function compile($str) {

        $tokens = $this->_eng->getTokenizer()->scan($str, '<% %>');
        $tree = $this->_eng->getParser()->parse($tokens);

        // get the tokenizer
        return array(
                $tree,
                $str
            );

    }

    public function render($str, $vars=array()) {

        // load any unload partials
        foreach ($this->getPartials() as $name => $file) {
            if (!array_key_exists($name, $this->_eng->_partials)) {
                $this->_eng->_partials[$name] = $file;
            }
        }

        // helpers
        foreach ($this->getHelpers() as $name => $cb) {
            $this->_eng->addHelper($name, $cb[0]);
        }

        // make sure variables are a bucket
        if (!b::isInterfaceOf($vars, '\bolt\iBucket')) {
            $vars = b::bucket($vars);
        }

        // try to render the string
        try {
            if (is_array($str)) {
                $t = new \Handlebars_Template($this->_eng, $str[0], $str[1]);
                $str = $t->render($vars);
            }
            else {
                $str = $this->_eng->render($str, $vars);
            }
        }
        catch(LogicException $e) { return; }

        // give it back
        return $str;

    }

    private function _addHelpers() {
        $helpers = array(
            '=' => function($template, $context, $args, $text) {
                if (preg_match_all('#\$([a-zA-Z0-9_\.]+)#', $args, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $val = $context->get($match[1]);
                        if (!$val AND $context->get('controller')) {
                            $val = $context->get('controller')->getParamValue($match[1]);
                        }
                        if (!$val) { return ""; }
                        if (!is_object($val)) { $val = b::bucket($val); }
                        ${$match[1]} = $val;
                    }
                }
                if (substr($args,-1) !== ';') { $args .=';'; }
                return eval("return $args");
            },
            'b' => function($template, $context, $args, $text) {
                $args = explode(' ', $args);

                $plugins = explode('.', array_shift($args));
                $last = array_pop($plugins);
                $b = b::bolt();
                foreach ($plugins as $plug) {
                    $b = call_user_func(array($b, $plug));
                }
                if (!is_object($b) OR !method_exists($b, $last)) {return;}
                return call_user_func_array(array($b, $last),$args);
            },
            'tag' => function($template, $context, $args, $text) {
                $var = $context->get('.');
                $tag = trim($args);

                $tag = '<'.$tag;

                if (isset($var->attr)) {
                    foreach ($var->attr as $k => $v) {
                        $tag .= ' '.$k.'="'.$v->encode().'"';
                    }
                }

                $tag .= '>';

                if (isset($var->text)) {
                    $tag .= $var->text;
                    $tag .= '</'.$tag.'>';
                }

                return $tag."\n";

            },
            'php' => function($template, $context, $args, $text) {
                if (preg_match_all('#\$([^\b]+)#i', $args, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $resp = $context->get($match[1]);

                        var_dump($context->get($match[1]), $match[1]); die;

                        if (b::isInterfaceOf($resp, '\bolt\iBucket')) {
                            $args = str_replace($match[0], $resp->export(), $args);
                        }
                    }
                }

                var_dump($args); die;
            },
            'set' => function ($template, $context, $args, $source) {
                list($var, $value) = explode(' ', trim($args));
                $context->push(array($var => $context->get($value)));
            },
            'eq' => function ($template, $context, $args, $source) {
                list($var, $value) = explode(' ', trim($args));
                $value = ($context->get($value) ?: trim($value,"'"));

                $tmp = $context->get($var);

                $buffer = '';
                if ($tmp == $value) {
                    $template->setStopToken('else');
                    $buffer = $template->render($context);
                    $template->setStopToken(false);
                    $template->discard($context);
                } else {
                    $template->setStopToken('else');
                    $template->discard($context);
                    $template->setStopToken(false);
                    $buffer = $template->render($context);
                }
                return $buffer;
            },
            'module' => function($template, $context, $args, $text) {
                $args = explode(' ', trim($args));
                $name = array_shift($args);
                $class = false;

                // no controller
                if (!class_exists('bolt\browser\controller\module')) {return;}

                if (array_key_exists($name, \bolt\browser\controller\module::$_modules)) {
                    $class = \bolt\browser\controller\module::$_modules[$name];
                }
                else if (class_exists($name)) {
                    $class = $name;
                }
                else {
                    return '<!-- NO MODULE '.$name.' !-->';
                }
                $params = b::bucket(array());
                if ($context->get('self')) {
                    $params = clone $context->get('self')->getParams();
                }

                $action = 'build';

                if (count($args)) {
                    $str = implode(" ", $args);


                    foreach (b::parseStringArguments($str) as $k => $v) {

                        if ($k === 'action') {
                            $action = $v;
                        }
                        else if ($v{0} == '$') {
                            $params->set($k, $context->get(substr($v,1)));
                        }
                        else {
                            $params->set($k, $v);
                        }

                    }

                }

                if (!empty($text) AND ($json = json_decode(trim($text), true)) !== null) {
                    foreach ($json as $k => $v) {
                        $params[$k] = $v;
                    }
                }


                $mod = new $class();
                return $mod($action, $params);
            }

        );
        foreach ($helpers as $name => $cb) {
            $this->_eng->addHelper($name, $cb);
        }
    }

}