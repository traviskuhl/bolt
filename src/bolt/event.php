<?php

namespace bolt;
use \b;

abstract class event {
    private $_events = array();

    // on
    public function on($name, $callback, $args=array()) {
        if (!array_key_exists($name, $this->_events)){ $this->_events[$name] = array(); }
        $eid = uniqid();
        $this->_events[$name] = array('callback' => $callback, 'args' => $args, 'eid' => $eid);
        return $eid;
    }

    public function fire($name, $args=array()) {
        if (array_key_exists($name, $this->_events)) {
            foreach ($this->_events as $event) {
                if (is_callable($event['callback'])) {
                    $args['args'] = $event['args'];
                    $args['eid'] = $event['eid'];
                    $args['this'] = $this;
                    call_user_func($event['callback'], $args);
                }
            }
        }

        // called clas is
        $class = get_called_class();

        // if we're not in bolt already
        // fire a global event
        if ($class != 'bolt') {
            b::fire(str_replace("\\", ":", $class).":{$name}", $args);
        }

        b::log("event fired from {$class} named {$name}");

    }

    public function getEvents($name=false) {
        if ($name AND !array_key_exists($name, $this->_events)) { return array(); }
        return ($name ? $this->_events[$name] : $this->_events);
    }

}