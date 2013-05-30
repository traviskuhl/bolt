<?php

namespace bolt\browser\controller;
use \b;

/**
 * callback controller
 *
 * @extends \bolt\browser\controller\request
 */
class callback extends request {

    // callback
    private $_callback = false;

    /**
     * set the callback
     *
     * @param Closure $callback callback function
     * @return self;
     */
    public function setCallback($callback) {
        $this->_callback = $callback;
        return $this;
    }

    /**
     * get the callback
     *
     * @return callback function
     */
    public function getCallback() {
        return $this->_callback();
    }

    /**
     * build the callback
     *
     * @return void
     */
    public function before() {

        // set action
        $this->setAction($this->_callback);

    }

}