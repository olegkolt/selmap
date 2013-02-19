<?php

namespace MiniLab\SelMap\Data\CellTypes;

use MiniLab\SelMap\Data\RecordSet;
use MiniLab\SelMap\Data\Record;
use MiniLab\SelMap\Model\Field;
use MiniLab\SelMap\Data\DataInterface;

/**
 *
 * Data class for row cell
 * 
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property-read Record $record Cell Record
 * @property-read Field  $field
 * @property      string $value  The value of Cell
 */
class Cell implements \JsonSerializable, DataInterface
{
    protected $value;
    protected $rel;
    protected $record;
    protected $field;
    /**
     * 
     * @param string $value Cell value
     * @param Record $rec   Current record
     * @param Field  $field
     */
    public function __construct($value, Record $rec, Field $field) {
        $this->record = $rec;
        $this->field = $field;
        $this->rel = new RecordSet();
        $this->value = static::validateOutput($value, $this->field);
    }
    /**
     * Get Cell value
     * 
     * @return string
     */
    public function __toString() {
        return (string)$this->value;
    }
    /**
     * @ignore
     */
    public function __set($name, $value)
    {
        if ($name == "value") {
            $this->value = static::validateInput($value, $this->field);
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
        $props = array("value", "field", "record", "rel");
        if (in_array($name, $props)) {
            return $this->$name;
        }
    }
    /**
     * Get relation by name
     * 
     * @param string $relName Format relTableName:relTableFieldName
     * @return RecordSet
     */
    public function getRel($relName)
    {
        return $this->rel[$relName];
    }
    /**
     * 
     * @param string $relName
     * @param Record $fRec
     * @return void
     */
    public function addSingleRel($relName, Record $fRec)
    {
        $this->rel[$relName] = $fRec;
    }
    /**
     * 
     * @param string $relName
     * @param Record $fRec
     * @param int    $fRecId
     * @return void
     */
    public function addMultipleRel($relName, Record $fRec, $fRecId)
    {
        if(!isset($this->rel[$relName])) {
            $this->rel[$relName] = new RecordSet();
        }
        $this->rel[$relName][$fRecId] = $fRec;
    }
    /**
     * Is it real cell or null-pattern object (EmptyCell). Cell allways return false
     * 
     * @return boolean
     */
    public function isEmpty()
    {
        return false;
    }
    /**
     * Get SQL code for update query
     * 
     * @return string
     */
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
    public static function validateInput($value, Field $field)
    {
        if(is_null($value) && $field->isNullable()) {
            return null;
        }
        return static::input($value);
    }
    public static function validateOutput($value, Field $field)
    {
        if(is_null($value) && $field->isNullable()) {
            return null;
        }
        return static::output($value);
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