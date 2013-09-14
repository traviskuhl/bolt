<?php

namespace bolt\client\compile;
use \b;

class routes extends base {

    // name of file
    const NAME = 'routes';

    // compile
    public static function compile($pkg) {

        //
        $router = b::route()->loadClassRoutes();

        // compiled
        $routes = array();

        // routes
        foreach ($router->getRoutes() as $route) {
            $routes[$route->compile()] = $route;
        }

        return array(
            'data' => $routes
        );

    }

}