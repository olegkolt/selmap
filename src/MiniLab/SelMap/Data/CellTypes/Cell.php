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
    /**
     * @var mixed
     */
    protected $value;
    /**
     * @var MiniLab\SelMap\Data\RecordSet
     */
    protected $rel;
    /**
     * @var MiniLab\SelMap\Data\Record
     */
    protected $record;
    /**
     * @var MiniLab\SelMap\Model\Field
     */
    protected $field;
    /**
     * 
     * @param string $value Cell value
     * @param Record $rec   Current record
     * @param Field  $field
     * @param bool   $isFromDB
     */
    public function __construct($value, Record $rec, Field $field, $isFromDB = false)
    {
        $this->record = $rec;
        $this->field = $field;
        $this->rel = new RecordSet();
        if($isFromDB) {
            $this->value = static::validateOutput($value, $this->field);
        } else {
            $this->value = static::validateInput($value, $this->field);
        }
    }
    /**
     * Get Cell value
     * 
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }
    /**
     * @ignore
     */
    public function __set($name, $value)
    {
        if ($name == "value") {
            $this->value = static::validateInput($value, $this->field);
            $this->record->addModified($this->field->name);
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
     * Add single relation. This record can have only one related record on foreign table
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
     * Add multiple relation. This record can have many related records on foreign table
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
        return "`" . $this->field->name . "` = '" . $this->escapeValue() . "'";
    }
    /**
     * Get prepared to query value
     * 
     * @return string
     */
    public function escapeValue()
    {
        $link = $this->record->table->db->getConn();
        return $link->real_escape_string($this->value);
    }
    /**
     * Serialize the Cell
     * 
     */
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