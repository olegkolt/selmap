<?php

namespace MiniLab\SelMap\Data\CellTypes;

use MiniLab\SelMap\DataBase;

class String extends Cell
{
    public static function input($value, DataBase $db)
    {
        return strval($value);
    }
    public static function output($value, DataBase $db)
    {
        return $value;
    }
}