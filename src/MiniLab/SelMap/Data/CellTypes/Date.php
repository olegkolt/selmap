<?php

namespace MiniLab\SelMap\Data\CellTypes;

use DateTime as DT;
use MiniLab\SelMap\DataBase;

class Date extends Cell
{
    public static function input($value, DataBase $db)
    {
        if(!($value instanceof DT)) {
            throw new \InvalidArgumentException("Must be instance of DateTime");
        }
        return $value->format('Y-m-d');
    }
    public static function output($value, DataBase $db)
    {
        return new DT($value);
    }
    /**
     * Return date("Y-m-d")
     * 
     * @return string Current date
     */
    public static function getNow() {
        return date("Y-m-d");
    }
}