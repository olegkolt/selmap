<?php

namespace MiniLab\SelMap\Model\Types;

class Integer
{
    public static function input($value)
    {
        return (int)$value;
    }
    public static function output($value)
    {
        return (int)$value;
    }
}