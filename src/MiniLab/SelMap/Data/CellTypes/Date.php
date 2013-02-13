<?php

namespace MiniLab\SelMap\Data\CellTypes;

use DateTime;

class Date extends Cell
{
    public static function input($value)
    {
        if(!($value instanceof \DateTime)) {
            throw new \InvalidArgumentException("Must be instance of DateTime");
        }
        return $value->format('Y-m-d');
    }
    public static function output($value)
    {
        return new DateTime($value);
    }
}