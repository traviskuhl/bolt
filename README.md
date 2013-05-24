# Bolt
a PHP Framework

## NOTICE
bolt is unstable and under active development. the api is unstable

[![Build Status](https://secure.travis-ci.org/traviskuhl/bolt.png?branch=beta)](http://travis-ci.org/traviskuhl/bolt)

## Install
`curl -Ls http://bolthq.com/install | php`

### Install from GIT
`php ./build/install`


## Quick Start
    <?php

    require "bolt.php";

    b::init(array(
        'mode' => "browser"
    ));

    // routes can be added as closures
    b::route('/', function(){

        return "hello world";

    })->method('get')
      ->before(function(){ b::reponse()->header->set('x-powered-by', 'bolt') });


    // or with controller classes
    class talk extends \bolt\borwser\controller {

        static $routes = array(
                array('route' => 'hello/{name}', 'name' => 'hello', 'action' => 'sayHello'),
                array('route' => 'goodbye/{name}', 'goodbye', 'action' => 'sayGoodbye')
            );

        public function sayHello($name) {
            return $this->renderString('Hello <% name %>. now <a href="<% url goodbye, name=$name %>">now say goodbye</a>');
        }

        public function sayGoodbye($name) {
            return $this->renderString('Goodbye <% name %>. now <a href="<% url hello, name=$name %>">now say hello</a>');
        }

    }

    b::run();