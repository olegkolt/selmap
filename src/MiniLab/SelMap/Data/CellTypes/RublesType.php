<?php
/**
 * This file is part of the SelMap package.
 *
 * (c) Oleg Koltunov <olegkolt@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MiniLab\SelMap\Data\CellTypes;

use Money\Money;
use Money\Currency;
use MiniLab\SelMap\DataBase;

/**
 * Cell type for Russion Rubles
 * 
 * @author Oleg Koltunov <olegkolt@mail.ru>
 *
 */
class RublesType extends CellType
{
    /**
     * To DB
     * 
     * @param Money    $value
     * @param DataBase $db
     * @throws \InvalidArgumentException
     * @return Money
     */
    public static function input($value, DataBase $db)
    {
        if(!($value instanceof Money)) {
            throw new \InvalidArgumentException("Field value must be Money");
        }
        if(! $value->getCurrency()->equals(new Currency("RUB"))) {
            throw new \InvalidArgumentException("Field value must be Rubles");
        }
        return $value;
    }
    /**
     * From DB
     * 
     * @param string   $value
     * @param DataBase $db
     */
    public static function output($value, DataBase $db)
    {
        //echo "output,";
        return Money::RUB(Money::stringToUnits($value));
    }
    /**
     * Escape cell value
     * 
     * @see \MiniLab\SelMap\Data\CellTypes\CellType::escapeValue()
     * @return string
     */
    public function escapeValue()
    {
        return self::moneyToDBFormat($this->value);
    }
    /**
     * Read money string
     * 
     * @param string $value
     * @return Money
     */
    public static function stringToMoney($value)
    {
        $value = str_replace(",", ".", $value);
        $value = str_replace(" ", "", $value);
        return Money::RUB(intval(floatval($value) * 100));
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
    /**
     * Transform to string. Delimiter - ","
     * 
     * @param Money $money
     * @return string
     */
    public static function moneyToString(Money $money)
    {
        return static::splitMoney($money, ",");
    }
    /**
     * Split to string
     * 
     * @param Money  $money
     * @param string $delimiter
     * @return string
     */
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