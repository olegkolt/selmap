<?php

namespace MiniLab\SelMap\Data;

use MiniLab\SelMap\Data\CellTypes\CellType;

class EmptyCell extends CellType
{
    public function __construct() {
    }
    public function __set($name, $value) {
       	throw new \Exception("Can not set property on EmptyCell object");
    }
    public function getUpdateSql() {
        throw new Exception("EmptyCell can not be saved in DB");
    }
    /**
     * Is it real cell or null-pattern object (EmptyCell). EmptyCell allways return 'true'
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return true;
    }
}