<?php

namespace bolt\source;
use \b;

interface bSource {

}

abstract class base implements bSource {

    abstract public function query($model, $query, $args=array());

    abstract public function row($model, $field, $value, $args=array());

    abstract public function insert($model, $data, $args=array());

    abstract public function update($model, $id, $data, $args=array());

    abstract public function delete($model, $id, $args=array());

    abstract public function count($model, $query, $args=array());

}