<?php

namespace bolt\browser\controller;
use \b;

/**
 * closure controller
 *
 * @extends \bolt\browser\controller\request
 */
class closure extends request {

    // closure
    private $_closure = false;

    /**
     * set the closure
     *
     * @param Closure $closure closure function
     * @return self;
     */
    public function setclosure($closure) {
        $this->_closure = $closure;
        return $this;
    }

    /**
     * get the closure
     *
     * @return closure function
     */
    public function getclosure() {
        return $this->_closure();
    }

    /**
     * build the closure
     *
     * @return void
     */
    public function before() {

        // set action
        $this->setAction($this->_closure);

    }

}