<?php

namespace MiniLab\SelMap\Data\CellTypes;

use MiniLab\SelMap\DataBase;

class String extends Cell
{
    public static function input($value, DataBase $db)
    {
        $link = $db->getConn();
        return $link->real_escape_string(strval($value));
    }
    public static function output($value, DataBase $db)
    {
        return $value;
    }
}