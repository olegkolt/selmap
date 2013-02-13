<?php

namespace MiniLab\SelMap\Data\CellTypes;

class String extends Cell
{
    public static function input($value)
    {
        return strval($value);
    }
    public static function output($value)
    {
        return $value;
    }
}