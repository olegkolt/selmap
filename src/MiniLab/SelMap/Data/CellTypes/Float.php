<?php

namespace MiniLab\SelMap\Data\CellTypes;

class Float extends Cell
{
    public static function input($value)
    {
        if(!is_numeric($value)) {
            throw new \InvalidArgumentException("Field value must be numeric");
        }
        return (float)$value;
    }
    public static function output($value)
    {
        return (float)$value;
    }
}