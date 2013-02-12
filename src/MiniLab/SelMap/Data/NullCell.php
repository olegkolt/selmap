<?php

namespace MiniLab\SelMap\Data;

class NullCell extends Cell {
    public function __construct() {
    }
    public function __set($name, $value) {
       	throw new \Exception("Can not set property on NullCell object");
    }
    public function getUpdateSql() {
        throw new Exception("NullCell can not be saved in DB");
    }
}