<?php

namespace MiniLab\SelMap\Data;

use MiniLab\SelMap\Data\CellTypes\Cell;

class EmptyCell extends Cell {
    public function __construct() {
    }
    public function __set($name, $value) {
       	throw new \Exception("Can not set property on EmptyCell object");
    }
    public function getUpdateSql() {
        throw new Exception("EmptyCell can not be saved in DB");
    }
}