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

        // classes
        $map = array();

        // loop through
        foreach ($commands as $cmd) {

            $class = $cmd->name;

            // desc
            $name = ($cmd->hasProperty('name') AND $cmd->getProperty('name')->isStatic()) ? $cmd->getProperty('name')->getValue() : $cmd->getShortName();
            $desc = ($cmd->hasProperty('desc') AND $cmd->getProperty('desc')->isStatic()) ? $cmd->getProperty('desc')->getValue() : false;
            $alias = ($cmd->hasProperty('alias') AND $cmd->getProperty('alias')->isStatic()) ? $cmd->getProperty('alias')->getValue() : array();
            $opts = ($cmd->hasProperty('options') AND $cmd->getProperty('options')->isStatic()) ? $cmd->getProperty('options')->getValue() : array();
            $opts = ($cmd->hasProperty('args') AND $cmd->getProperty('args')->isStatic()) ? $cmd->getProperty('args')->getValue() : array();

            $commands = ($cmd->hasMethod('commands') ? $class::commands() : array());

            // add the command
            $c = $parser->addCommand($name, array(
                    'description' => $desc,
                    'aliases' => $alias
                ));

            // map command name to class
            $map[$name] = $cmd->name;

            // add
            foreach ($opts as $oName => $opt) {
                $c->addOption($oName, $opt);
            }

            $exec = $cmd->getMethod('execute');

            // arguments
            if ($exec->getNumberOfParameters() > 0) {
                foreach ($exec->getParameters() as $param) {
                    $c->addArgument(
                        $param->name,
                        array(
                            'optional' => $param->isOptional()
                        )
                    );
                }
            }

            // add commands
            foreach ($commands as $subName => $subOpts) {
                if ($cmd->hasMethod($subName)) {
                    $sub = $c->addCommand($subName, array(
                            'description' => p('description', false, $subOpts),
                            'aliases' => p('alias', array(), $subOpts)
                        ));
                    if (isset($subOpts['options'])) {
                        foreach ($subOpts as $sName => $opt) {
                            $sub->addOption($sName, $opt);
                        }
                    }
                    $method = $cmd->getMethod($subName);
                    // arguments
                    if ($method->getNumberOfParameters() > 0) {
                        foreach ($method->getParameters() as $param) {
                            $sub->addArgument(
                                $param->name,
                                array(
                                    'optional' => $param->isOptional()
                                )
                            );
                        }
                    }
                }
            }

        }

        // try to parse
        try {

            // get the result
            $result = $parser->parse();

            // run it
            if ($result->command_name AND array_key_exists($result->command_name, $map)) {
                $class = new $map[$result->command_name]();

                // holders
                $name = ($result->command->command_name ? $result->command->command_name : "execute");
                $cmd = ($result->command->command_name ? $result->command : $result);

                // args
                $args = array();

                // reflect
                $method = new \ReflectionMethod($class, $name);

                if ($method->getNumberOfParameters() > 0) {
                    foreach ($method->getParameters() as $param) {
                        $args[] = (isset($cmd->args[$param->name]) ? $cmd->args[$param->name] : $param->getDefaultValue());
                    }
                }

                call_user_func_array(array($class, $name), $args);

            }

        }
        catch (Exception $e) {

        }


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
