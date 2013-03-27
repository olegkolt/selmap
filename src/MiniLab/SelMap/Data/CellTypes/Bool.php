<?php

namespace MiniLab\SelMap\Data\CellTypes;

use MiniLab\SelMap\DataBase;

class Bool extends Cell
{
    public static function input($value, DataBase $db)
    {
        return (bool)$value;
    }
    public static function output($value, DataBase $db)
    {
        return (bool)$value;
    }
}