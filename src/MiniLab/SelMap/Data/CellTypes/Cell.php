<?php

namespace MiniLab\SelMap\Data\CellTypes;

use MiniLab\SelMap\Data\Record;
use MiniLab\SelMap\Model\Field;

/**
 *
 * Data class for row cell
 * 
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property-read Record $record Cell Record
 * @property-read Field  $field
 * @property      string $value  The value of Cell
 */
class Cell implements \JsonSerializable
{
    protected $value;
    public $rel = array();
    protected $record;
    protected $field;
    /**
     * 
     * @param string $value Cell value
     * @param Record $rec   Current record
     * @param Field  $field
     */
    public function __construct($value, Record $rec, Field $field) {
        $this->value = static::output($value);
        $this->record = $rec;
        $this->field = $field;
    }
    public function __toString() {
        return (string)$this->value;
    }
    /**
     * @ignore
     */
    public function __set($name, $value)
    {
        if ($name == "value") {
            $this->value = static::input($value);
            $this->record->addModified($this->fieldName);
        }
        else{
            throw new \Exception("Can not set property " . $name);
        }
    }
    /**
     * @ignore
     */
    public function __get($name)
    {
        $props = array("value", "field", "record");
        if (in_array($name, $props)) {
            return $this->$name;
        }
    }
    public function getUpdateSql()
    {
        $link = $this->record->table->db->getConn();
        $value = $link->real_escape_string($this->value);
        //$value = addslashes($this->value);
        return "`" . $this->field->name . "` = '" . $value . "'";
    }
    public function jsonSerialize()
    {
        $result = array("value" => $this->value);
        if(count($this->rel) > 0) {
            $result["rel"] = $this->rel;
        }
        return $result;
    }
    public static function input($value)
    {
        return $value;
    }
    public static function output($value)
    {
        return $value;
    }
}