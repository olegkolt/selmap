<?php

namespace MiniLab\SelMap\Data\CellTypes;

use MiniLab\SelMap\Data\Record;
use MiniLab\SelMap\Model\Field;

class Balance extends Cell
{
    protected $initialValue;
    /**
     * 
     * @param float  $value
     * @param Record $rec
     * @param Field  $fieldName
     */
    public function __construct($value, Record $rec, Field $field)
    {
        parent::__construct(floatval($value), $rec, $field);
        $this->initialValue = floatval($value);
    }
    public function getUpdateSql()
    {
        $diff = $this->value - $this->initialValue;
        return "`" . $this->field->name . "` = `" . $this->fieldName . "` + '" . $diff . "'";
    }
    /**
     * @ignore
     */
    public function __set($name, $value)
    {
        if ($name == "value") {
            $value = floatval($value);
        }
        parent::__set($name, $value);
    }
    public static function input($value)
    {
        return Rubles::input($value);
    }
    public static function output($value)
    {
        return Rubles::output($value);
    }
}