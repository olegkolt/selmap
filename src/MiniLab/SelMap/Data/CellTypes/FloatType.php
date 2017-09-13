<?php

namespace MiniLab\SelMap\Data\CellTypes;

use MiniLab\SelMap\DataBase;

class FloatType extends CellType
{
    public static function input($value, DataBase $db)
    {
        if(!is_numeric($value)) {
            throw new \InvalidArgumentException("Field value must be numeric");
        }
        return (float)$value;
    }
    public static function output($value, DataBase $db)
    {
        return (float)$value;
    }
}