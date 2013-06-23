<?php

namespace bolt\source;
use \b;

abstract class base {

    abstract public function query($table, $query, $args=array());

    abstract public function insert($table, $data, $args=array());

    abstract public function update($table, $id, $data, $args=array());

    abstract public function delete($table, $id, $args=array());

    abstract public function count($table, $query, $args=array());

}