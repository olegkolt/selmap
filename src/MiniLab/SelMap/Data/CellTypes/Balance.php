<?php

namespace MiniLab\SelMap\Data\CellTypes;

use MiniLab\SelMap\Data\Record;
use MiniLab\SelMap\Model\Field;

class Balance extends Rubles
{
    /**
     * Initial money value
     * 
     * @var Money\Money
     */
    protected $initialValue;
    /**
     * 
     * @param float  $value
     * @param Record $rec
     * @param Field  $fieldName
     */
    public function __construct($value, Record $rec, Field $field, $isFromDB = false)
    {
        parent::__construct($value, $rec, $field, $isFromDB);
        if($isFromDB === false) {
            $value = 0;
        }
        $this->initialValue = Rubles::output($value);
    }
    public function getUpdateSql()
    {
        $diff = $this->value->subtract($this->initialValue);
        $diff = Rubles::moneyToDBFormat($diff);
        return "`" . $this->field->name . "` = `" . $this->fieldName . "` + '" . $diff . "'";
    }
}