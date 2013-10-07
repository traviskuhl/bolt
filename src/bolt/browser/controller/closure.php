<?php

namespace bolt\browser\controller;
use \b;

/**
 * closure controller
 *
 * @extends \bolt\browser\controller\request
 */
class closure extends  \bolt\browser\controller {

    // closure
    private $_closure = false;


    /**
     * set the closure
     *
     * @param Closure $closure closure function
     * @return self;
     */
    public function setClosure($closure) {
        $this->_closure = $closure;
        return $this;
    }

    /**
     * get the closure
     *
     * @return closure function
     */
    public function getClosure() {
        return $this->_closure;
    }

    public function invoke() {

        return call_user_func($this->getClosure());

    }

}