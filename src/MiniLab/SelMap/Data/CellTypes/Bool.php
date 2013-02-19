<?php

namespace MiniLab\SelMap\Data\CellTypes;

class Bool extends Cell
{
    public static function input($value)
    {
        return (bool)$value;
    }
    public static function output($value)
    {
        return (bool)$value;
    }
}