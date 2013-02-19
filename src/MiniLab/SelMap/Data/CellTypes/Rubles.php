<?php

namespace MiniLab\SelMap\Data\CellTypes;

use Money\Money;
use Money\Currency;

class Rubles extends Cell
{
    public static function input($value)
    {
        if(!($value instanceof Money)) {
            throw new \InvalidArgumentException("Field value must be Money");
        }
        if(! $value->getCurrency()->equals(new Currency("RUB"))) {
            throw new \InvalidArgumentException("Field value must be Rubles");
        }
        return $value;
    }
    public static function output($value)
    {
        return Money::RUB(Money::stringToUnits($value));
    }
}