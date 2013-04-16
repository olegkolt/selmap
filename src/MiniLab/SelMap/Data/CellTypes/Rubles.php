<?php

namespace MiniLab\SelMap\Data\CellTypes;

use Money\Money;
use Money\Currency;
use MiniLab\SelMap\DataBase;

class Rubles extends Cell
{
    public static function input($value, DataBase $db)
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
    public static function output($value, DataBase $db)
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
        return static::splitMoney($money, ".");
    }
    public static function moneyToString(Money $money)
    {
        return static::splitMoney($money, ",");
    }
    private static function splitMoney(Money $money, $delimiter)
    {
        $amount = (string)$money->getAmount();
        if($amount == "0") {
            return "0" . $delimiter . "00";
        }
        $kopecks = substr($amount, -2);
        $rubles  = substr($amount, 0, -2);
        if($rubles == "") {
            $rubles = 0;
        }
        return $rubles . $delimiter . $kopecks;
    }
}