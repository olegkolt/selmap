<?php

namespace MiniLab\SelMap\Data\CellTypes;

class Int extends Cell
{
    public static function input($value)
    {
        if($value == "") {
            $value = 0;
        }
        if(!is_numeric($value)) {
            throw new \InvalidArgumentException("Field value must be numeric. '" . $value . "' given");
        }
        return (int)$value;
    }
    public static function output($value)
    {
        return (int)$value;
    }
}