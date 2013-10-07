<?php

namespace bolt;
use \b;

// depend on bolt core and plugin browser
b::depend('bolt-core-*')->plug('browser', '\bolt\browser');

class browser extends \bolt\plugin\singleton {


    private $_route = false;
    private $_request = false;
    private $_response = false;

    public function run() {

        // start our request
        $this->_request = new browser\request();
        $this->_response = browser\response::initByType('plain');

        // ask the router to look for classes
        b::route()->loadClassRoutes();

        // pathInfo
        $pathInfo = trim(ltrim(bPathInfo,'/'));
        $method = $this->_request->getMethod();

        // fire lets run our router to figure out what
        // route we need to take
        $this->_route = b::route()->match($pathInfo, $method);


        // things we'll use
        $action = 'build';
        $params = array();

        // no route just die right now
        if (!$this->_route) {
            $controller = b::browser()->error("No route for path '".strtoupper($method)." {$pathInfo}'", 404);
            return $this->render($controller->getResponseByType('html'));
        }
        else  {

            // start our base response
            $this->_response = browser\response::initByType($this->_route->getResponseType());

            // set our route in the request
            $this->_request->setRoute($this->_route);

            // get the controller from the route
            $class = $this->_route->getController();
            $action = $this->_route->getAction();
            $params = $this->_route->getParams();
            $type = $this->_route->getResponseType();

            if (is_a($class, 'bolt\browser\controller\closure')) {
                $controller = $class;
            }
            else {

                // create a new controller obj
                $controller = new $class(array(
                    'request' => $this->_request,
                    'response' => $this->_response,
                    'route' => $this->_route,
                    'params' => $params
                ));

            }

            // route before
            $resp = $this->_route->fireBefore();

            if (b::isInterfaceOf($resp, '\bolt\browser\iController')) {
                return $this->render($resp->invoke()->getResponseByType($type));
            }
            if (b::isInterfaceOf($resp, '\bolt\browser\iResponse')) {
                return $this->render($resp->getResponseByType($type));
            }

        }

        // run start
        $this->fire("before", array('controller' => $controller));

        // invoke the controller
        $resp = $controller->invoke($action, $params);

            // if resp is a controller
            // set it
            if (b::isInterfaceOf($resp, '\bolt\browser\iController')) {
                $resp->setResponseType($controller->getResponseType()); // match response type of original request
                $controller = $resp;
            }


        $type = $controller->getResponseType();

        // ask the controller for it
        $resp = $controller->getResponseByType($type);

        // no response
        if (!$resp) {
            return b::browser()->fail("No response for path '".strtoupper($method)." {$pathInfo}' of type '{$type}'", 404);
        }

        // is the response a controller?
        if (b::isInterfaceOf($resp, '\bolt\browser\iController')) {
            $resp = $resp->invoke($action, $params);
        }

        $this->fire('after');

        return $this->render($resp);

    }

    public function render($resp) {

        // print a content type
        if (!headers_sent()) {

            header("Content-Type: {$resp->getContentType()}", true, (int)$resp->getStatus());

            // print all headers
            $resp->getHeaders()->map(function($name, $value){
                header("$name: $value");
            });

        }

        // respond
        exit($resp->getResponse());

    }

    public function getRoute() {
        return $this->_route;
    }

    public function getRequest() {
        return $this->_request;
    }

    public function getResponse() {
        return $this->_response;
    }

    public function redirect($url) {
        $c =  new \bolt\browser\controller\redirect();
        $c->setUrl($url);
        return $c;
    }

    public function fail($message, $code) {
        header('Content-Type:text/html', true, (int)$code);
        exit('<!doctype html>
                <html>
                    <body>
                        <h1>Error: '.$code.'</h1>
                        <p>'.$message.'</p>
                    </body>
                </html>
            ');
    }

    public function error($message, $code=500) {

        $errors = b::settings()->value('project.errors');
        if (array_key_exists($code, $errors) OR array_key_exists('default', $errors) ) {
            $m = (array_key_exists($code, $errors) ? $errors[$code] : $errors['default']);
            $c = b::controller($m);
            $c->setResponseType('html');
            $c->invoke('get');
        }
        else {
            $c = new \bolt\browser\controller();
            $c->responses(array(
                'html' => '<!doctype html>
                    <html>
                        <body>
                            <h1>Error: '.$code.'</h1>
                            <p>'.$message.'</p>
                        </body>
                    </html>
                ',
                'json' => array(
                    'error' => $message
                )
            ));
        }
        $c->getResponse()->setStatus($code);
        return $c;
    }


}