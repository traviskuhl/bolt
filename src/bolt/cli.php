<?php

namespace bolt;
use \b as b;

// plugin
b::plug('cli', '\bolt\cli');

// use a package package
require "Console/CommandLine.php";
use \Console_CommandLine;

// source
class cli extends plugin {

    // type is singleton
    // since this is really a plugin dispatch
    public static $TYPE = "singleton";

    // hold our commands
    private $_commands = array();


    // 
    public function run() {
        global $argv;

        // look for all commands
        $commands = b::getDefinedSubClasses('\bolt\cli\command');
        $options = false;

        // parser
        $parser = new Console_CommandLine();

        // loop through
        foreach ($commands as $cmd) {

            // desc
            $name = ($cmd->hasProperty('name') AND $cmd->getProperty('name')->isStatic()) ? $cmd->getProperty('name')->getValue() : $cmd->getShortName();
            $desc = ($cmd->hasProperty('desc') AND $cmd->getProperty('desc')->isStatic()) ? $cmd->getProperty('desc')->getValue() : false;
            $alias = ($cmd->hasProperty('alias') AND $cmd->getProperty('alias')->isStatic()) ? $cmd->getProperty('alias')->getValue() : array();
            $opts = ($cmd->hasProperty('options') AND $cmd->getProperty('options')->isStatic()) ? $cmd->getProperty('options')->getValue() : array();
            $opts = ($cmd->hasProperty('args') AND $cmd->getProperty('args')->isStatic()) ? $cmd->getProperty('args')->getValue() : array();
        
            $commands = ($cmd->hasProperty('commands') AND $cmd->getProperty('commands')->isStatic()) ? $cmd->getProperty('commands')->getValue() : array();

            if (!$cmd->hasMethod('run')) {continue;}

            // add the command
            $c = $parser->addCommand($name, array(
                    'description' => $desc,
                    'aliases' => $alias
                ));

            // add
            foreach ($opts as $oName => $opt) {
                $c->addOption($oName, $opt);
            }

            $run = $cmd->getMethod('run');

            // arguments
            foreach ($args as $aName => $opts) {
                $c->addArgument($aName, $opts);
            }

            // add commands
            foreach ($commands as $subName => $subOpts) {
                if ($cmd->hasMethod($subName)) {
                    $sub = $parser->addCommand(implode(':',array($name, $subName)), array(
                            'description' => p('description', false, $subOpts),
                            'aliases' => p('alias', array(), $subOpts)
                        ));
                    if (isset($subOpts['options'])) {
                        foreach ($subOpts as $sName => $opt) {
                            $sub->addOption($sName, $opt);
                        }
                    }
                    if (isset($subOpts['args'])) {
                        foreach ($args as $aName => $opts) {
                            $c->addArgument($aName, $opts);
                        }
                    }
                }
            }

        }

        var_dump($parser); die;
   
    }

    public function unknown() {
        return $this->error("Unknown Command");
    }

    // ask
    public function ask($q, $default=false) {
        $this->out($q.($default ? " [{$default}]: " : ": "));
        $a = trim(fgets(STDIN));
        return (empty($a) ? $default : $a);
    }
    public function askYesNo($q, $default='y') {
        $d = strtolower($default{0});
        $this->out($q.($default == 'n' ? " [yes/No]: " : " [Yes/no]: "));
        $a = strtolower(trim(fgets(STDIN)));
        if (!$a) { $a = $d; }
        return ($a{0} == 'y' ? true : false);
    }

    public function line() {
        if (is_array(func_get_arg(0))) {
            foreach (func_get_arg(0) as $line) {
                echo call_user_func_array(array($this, 'line'), $line);
            }
            return;
        }
        else {
            echo call_user_func_array('sprintf', func_get_args())."\n";
        }
    }
}
