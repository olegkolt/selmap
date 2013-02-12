<?php

namespace MiniLab\SelMap\Data;

class BalanceCell extends Cell{
    protected $initialValue;
    /**
     * 
     * @param float  $value
     * @param Record $rec
     * @param string $fieldName
     */
    public function __construct($value, Record $rec, $fieldName){
        parent::__construct(floatval($value), $rec, $fieldName);
        $this->initialValue = floatval($value);
    }
    public function getUpdateSql() {
        $diff = $this->value - $this->initialValue;
        return "`" . $this->fieldName . "` = `" . $this->fieldName . "` + '" . $diff . "'";
    }
    /**
     * @ignore
     */
    public function __set($name, $value) {
        if ($name == "value") {
            $value = floatval($value);
        }
        parent::__set($name, $value);
    }
}