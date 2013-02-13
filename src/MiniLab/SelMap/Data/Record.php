<?php

namespace MiniLab\SelMap\Data;

use MiniLab\SelMap\Model\Table;
use MiniLab\SelMap\DataBase;
//use MiniLab\SelMap\Data\Cell;

/**
 * Enter description here ...
 * 
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property-read string $pKeyField Primary key field name
 * @property-read string $pk        Value of record's primary key
 * @property-read Table  $table     Table object
 */
class Record implements \ArrayAccess, \Iterator, \JsonSerializable {
    protected $cell = array();
    protected $modified = array();
    protected $position = 0;
    protected $fields = array();
    protected $inserted = false;

    protected $pKeyField;
    protected $pk;
    protected $table;

    protected $db;
    /**
     *
     * @param Table $table
     * @param array($fieldName => $value) $rowArray
     */
    public function __construct(Table $table, array $rowArray = array()) {
        $this->pKeyField = $table->pKeyField;
        $this->table = $table;
        $this->db = $table->db;
        foreach ($rowArray as $k => $v) {
            $this->offsetSet($k, $v);
        }
        $this->modified = array();
    }
    /**
     * @ignore
     */
    public function __get($name) {
        $props = array("pKeyField", "pk", "table");
        if (in_array($name, $props)) {
            return $this->$name;
        }
    }
    protected function pkCheck() {
        if (isset($this->cell[(string)$this->pKeyField])) {
            $this->pk = $this->cell[(string)$this->pKeyField];
        }
    }
    public function addRel($cellName, $fTableName, $fKey, Record $fRecord, $id = null) {
        if (is_null($id)) {
            $this->cell[$cellName]->rel[$fTableName . ":" . $fKey] = $fRecord;
        } else {
            $this->cell[$cellName]->rel[$fTableName . ":" . $fKey][$id] = $fRecord;
        }
    }
    public function addModified($fieldName) {
        $this->modified[$fieldName] = true;
    }
    protected function createCell($value, $fieldName)
    {
        $field = $this->table->fields[$fieldName];
        $type = DataBase::CELL_TYPES_NAMESPACE . $field->type;
        return new $type($value, $this, $field);
    }
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            throw new \Exception("Field name not valid");
        }
        if ($value instanceof Cell) {
            if($value->record->table->name == $this->table->name &&
                    $offset == $value->fieldName) {
                $this->cell[$offset] = $value;
            } else {
                $value = $this->createCell($value->value, $offset);
                $this->cell[$offset] = $value;
            }
        } else {
            if (isset($this->cell[$offset])) {
                $this->cell[$offset]->value = $value;
            } else {
                $value = $this->createCell($value, $offset);
                $this->cell[$offset] = $value;
            }
        }
        $this->modified[$offset] = true;
        if (!in_array($offset, $this->fields)) {
            $this->fields[] = $offset;
        }
        $this->pkCheck();
    }
    public function offsetExists($offset) {
        return isset($this->cell[$offset]);
    }
    public function offsetUnset($offset) {
        unset($this->fields[array_search($offset, $this->fields) ]);
        unset($this->cell[$offset]);
    }
    public function offsetGet($offset) {
        return isset($this->cell[$offset]) ? $this->cell[$offset] : null;
    }
    public function rewind() {
        $this->position = 0;
    }
    public function current() {
        $field = $this->fields[$this->position];
        return $this->cell[$field];
    }
    public function key() {
        return $this->fields[$this->position];
    }
    public function next() {
        ++$this->position;
    }
    public function valid() {
        return isset($this->fields[$this->position]);
    }
    /**
     * Save current row
     * 
     * @return string row PK
     */
    public function save() {
        //echo "Save:" . $this->table->name . ", ";
        if (!$this->havePK()) {
            return $this->insert();
        } else {
            $this->update();
            return $this->pk;
        }
    }
    public function havePK() {
        $this->pkCheck();
        $pk = (string)$this->pk;
        return !($pk == "" || $pk == "0");
    }
    /**
     * Insert current row
     * 
     * @return string inserted id
     */
    public function insert() {
        if ($this->inserted) {
            return $this->pk;
        }
        //echo "insert " . $this->table->name;
        foreach ($this->cell as $field => $v) {
            foreach ($v->rel as $relName => $fRec) {
                list($tableName, $fKey) = explode(":", $relName);
                 
                if(!isset($this->table->fields[$field]->rel[$relName])) {
                    //throw new \Exception("Can not found relation '" . $relName . "' on table '" . $this->table->name . "' and field '" . $field . "'");
                    continue;
                    // обход бага в нескольких Multirecord на странице
                }

                $relation = $this->table->fields[$field]->rel[$relName];
                //var_dump($relation->crossRel->isFTableArray());
                if ($relation->inherite ||
                        (($fRec instanceof Record) && $v->value == "" &&
                                ($relation instanceof Relation) && ($relation->crossRel->isFTableArray()))) {
                    //echo " " . $relName . " ";
                    //var_dump($relation->inherite);
                    //var_dump($relation->crossRel->isFTableArray());
                    //echo "<br />";
                    $v->value = $fRec->save();
                }
            }
        }
        $fields = array();
        $values = array();
        foreach ($this->cell as $f => $v) {
            $fields[] = "`" . $f . "`";
            if (is_null($v->value)) {
                $values[] = "NULL";
            } else {
                $values[] = "'" . addslashes($v->value) . "'";
            }
        }
        $query = "INSERT INTO `" . $this->table->name . "` (" . implode(", ", $fields) . ")" . " VALUES (" . implode(", ", $values) . ")";
        $this->db->execNonResult($query);
        $this->inserted = true;
        $this->modified = array();
        if (!$this->havePK()) {
            $this->offsetSet((string)$this->pKeyField, $this->db->insertId());
        }
        if ((string)$this->pk == "0") {
            throw new \Exception("PK is 0. Query: " . $query);
        }
        return $this->pk;
    }
    public function update(array $cells = null) {
        if (is_null($cells)) {
            $cells = array_keys($this->modified);
        }
        if (array_search($this->pKeyField, $cells) !== false) {
            unset($cells[array_search($this->pKeyField, $cells) ]);
        }
        if (count($cells) == 0) {
            return false;
        }
        if (count($this->cell) == 0) {
            throw new \Exception("Call update on empty Record");
        }
        $query = "UPDATE `" . $this->table->name . "` SET ";
        foreach ($this->cell as $key => $value) {
            if (!in_array($key, $cells)) {
                continue;
            }
            if (is_null($value->value)) {
                $query.= "`" . $key . "` = NULL, ";
            } else {
                //$query.= "`" . $key . "` = '" . addslashes($value->value) . "', ";
                $query .= $value->getUpdateSql() . ", ";
            }
        }
        $query = substr($query, 0, -2);
        $pk = (string)$this->pKeyField;
        $query.= " WHERE `" . $this->pKeyField . "` = '" . $this->cell[$pk] . "'";
        $this->db->execNonResult($query);
        //echo $query. "<br />";
        $this->modified = array();
        return true;
    }
    public function jsonSerialize() {
        $result = $this->cell;
        $result["_pk"] = $this->pk;
        $result["_pKeyField"] = (string)$this->pKeyField;
        return $result;
    }
}