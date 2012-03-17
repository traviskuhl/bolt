# Bolt
php framework

[![Build Status](https://secure.travis-ci.org/traviskuhl/bolt.png?branch=beta)](http://travis-ci.org/traviskuhl/bolt)

## Features
* simple and lightweight
* plugable articture
* out-of-the-box support for sessions and accounts
* other cool stuff

## Example
    include("./bolt.php");
    
    // initate our bolt instance
    // and set some config variables 
    b::init(array(
        'config' => array(
            'autoload' => array(
                "./pages"
            ),
            'mongo' => array(
                'host' => "127.0.0.1",
                'port' => 27017,
                'db' => "test"
            ),
            'session' => array(
                'cookie' => 's',
                'exp' => '+2 weeks'
            ),
            'defaultView' => "testRoute"
        )
    ));

    // add a display route
    b::route("test/([a-z]+)>name/([a-z]+)>place", '\testRoute', "test");
    
    // this is our route
    class testRoute extends \bolt\view  {
        function get() {
            $params = array(
                'name' => $this->param('name'),
                'place' => $this->param('place')
            );
            $this->render()->string('{$name} {$place}', $params);
        }
    }    
    
    // tell bolt to execute the router
    // and display the resulting page
    b::run(array(
        'path' => "test/hello/world"
    ));