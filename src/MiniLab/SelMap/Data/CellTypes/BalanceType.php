<?php

namespace MiniLab\SelMap\Data\CellTypes;

use MiniLab\SelMap\Data\Record;
use MiniLab\SelMap\Model\Field;

class BalanceType extends RublesType
{
    /**
     * Initial money value
     * 
     * @var \Money\Money
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
        $this->initialValue = RublesType::output($value, $this->db);
    }
    public function getUpdateSql()
    {
        $diff = $this->value->subtract($this->initialValue);
        $diff = RublesType::moneyToDBFormat($diff);
        return "`" . $this->field->name . "` = `" . $this->fieldName . "` + '" . $diff . "'";
    }
}