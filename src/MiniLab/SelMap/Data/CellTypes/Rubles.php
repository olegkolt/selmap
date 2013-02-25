<?php

namespace MiniLab\SelMap\Data\CellTypes;

use Money\Money;
use Money\Currency;

class Rubles extends Cell
{
    public static function input($value)
    {
        //echo "input,";
        if(!($value instanceof Money)) {
            var_dump($value);
            throw new \InvalidArgumentException("Field value must be Money");
        }
        if(! $value->getCurrency()->equals(new Currency("RUB"))) {
            throw new \InvalidArgumentException("Field value must be Rubles");
        }
        return $value;
    }
    public static function output($value)
    {
        //echo "output,";
        return Money::RUB(Money::stringToUnits($value));
    }
    public function escapeValue()
    {
        return self::moneyToDBFormat($this->value);
    }
    /**
     * Transform to MySQL decimal type
     * 
     * @param Money $money
     * @return string
     */
    public static function moneyToDBFormat(Money $money)
    {
        $amount = (string)$money->getAmount();
        $kopecks = substr($amount, -2);
        $rubles  = substr($amount, 0, -2);
        return $rubles . "." . $kopecks;
    }
}