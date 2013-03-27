<?php

namespace MiniLab\SelMap\Data\CellTypes;

use MiniLab\SelMap\DataBase;

class Int extends Cell
{
    public static function input($value, DataBase $db)
    {
        if($value == "") {
            $value = 0;
        }
        if(!is_numeric($value)) {
            throw new \InvalidArgumentException("Field value must be numeric. '" . $value . "' given");
        }
        return (int)$value;
    }
    public static function output($value, DataBase $db)
    {
        return (int)$value;
    }
}