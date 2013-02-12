<?php

namespace MiniLab\SelMap\Data;

/**
 *
 * Data class for row cell
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property-read Record $record Cell Record
 * @property-read string $fieldName Name of the field
 * @property string $value The value of Cell
 */
class Cell implements \JsonSerializable {
    protected $value;
    public $rel = array();
    protected $record;
    protected $fieldName;
    /**
     *
     * Enter description here ...
     * @param string $value Cell value
     * @param Record $rec Current record
     * @param string $fieldName Current field name
     */
    public function __construct($value, Record $rec, $fieldName) {
        $this->value = $value;
        $this->record = $rec;
        $this->fieldName = $fieldName;
    }
    public function __toString() {
        return (string)$this->value;
    }
    /**
     * @ignore
     */
    public function __set($name, $value) {
        if ($name == "value") {
            $this->value = $value;
            $this->record->addModified($this->fieldName);
        }
        else{
            throw new \Exception("Can not set property " . $name);
        }
    }
    /**
     * @ignore
     */
    public function __get($name) {
        $props = array("value", "fieldName", "record");
        if (in_array($name, $props)) {
            return $this->$name;
        }
    }
    public function getUpdateSql() {
        $link = $this->record->table->db->getConn();
        $value = $link->real_escape_string($this->value);
        //$value = addslashes($this->value);
        return "`" . $this->fieldName . "` = '" . $value . "'";
    }
    public function jsonSerialize() {
        $result = array("value" => $this->value);
        if(count($this->rel) > 0) {
            $result["rel"] = $this->rel;
        }
        return $result;
    }
}