<?php

namespace MiniLab\SelMap\Data;

use MiniLab\SelMap\Model\Table;
use MiniLab\SelMap\DataBase;
use MiniLab\SelMap\Data\CellTypes\CellType;
use MiniLab\SelMap\Model\Relation;

/**
 * Record class represent db single row
 * 
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property-read Field    $pKeyField Primary key field name
 * @property-read string   $pk        Value of record's primary key
 * @property-read Table    $table     Table object
 * @property-read DataBase $db        DataBase object
 */
class Record implements \ArrayAccess, \Iterator, \JsonSerializable, DataInterface
{
    /**
     * Array of Cell objects
     * 
     * @var array(Cell)
     */
    protected $cell = array();
    /**
     * Modified fields
     * 
     * @var array(string)
     */
    protected $modified = array();
    /**
     * Needed for Iterator interface
     * 
     * @var int
     */
    protected $position = 0;
    /**
     * Array of record fields
     * 
     * @var array(string)
     */
    protected $fields = array();
    /**
     * True if record has been already inserted to DB
     * 
     * @var bool
     */
    protected $inserted = false;
    /**
     * Record's primary key field
     * 
     * @var MiniLab\SelMap\Model\Field
     */
    protected $pKeyField;
    /**
     * Record's primary key
     * 
     * @var MiniLab\SelMap\Data\CellTypes\Cell
     */
    protected $pk;
    /**
     * Record's table
     * 
     * @var MiniLab\SelMap\Model\Table
     */
    protected $table;
    /**
     * DataBase link
     * 
     * @var MiniLab\SelMap\DataBase
     */
    protected $db;
    /**
     * Create record instance
     *
     * @param Table $table
     * @param array($fieldName => $value) $rowArray
     * @param bool $isFromDB
     */
    public function __construct(Table $table, array $rowArray = array(), $isFromDB = false)
    {
        $this->pKeyField = $table->pKeyField;
        $this->table = $table;
        $this->db = $table->db;
        foreach ($rowArray as $k => $v) {
            $k = (string)$k;
            $value = $this->createCell($v, $k, $isFromDB);
            $this->cell[$k] = $value;
            if (!in_array($k, $this->fields)){
                $this->fields[] = $k;
            }
        }
        $this->modified = array();
        $this->pkCheck();
    }
    /**
     * @ignore
     */
    public function __get($name)
    {
        $props = array("pKeyField", "pk", "table", "db");
        if (in_array($name, $props)) {
            return $this->$name;
        }
    }
    /**
     * Set $this->pk if isset value of pk field
     * 
     * @return void
     */
    protected function pkCheck()
    {
        if (isset($this->cell[(string)$this->pKeyField])) {
            $this->pk = $this->cell[(string)$this->pKeyField];
        }
    }
    /**
     * Mark field as modified
     * 
     * @param string $fieldName
     * @return void
     */
    public function addModified($fieldName)
    {
        if(in_array($fieldName, $this->modified)) {
            return;
        }
        $this->modified[] = $fieldName;
    }
    /**
     * Create Cell object
     * 
     * @param string $value
     * @param string $fieldName
     * @param bool   $isFromDB
     * @return CellType
     */
    protected function createCell($value, $fieldName, $isFromDB = false)
    {
        $field = $this->table->fields[$fieldName];
        $type = DataBase::buildCellTypeClassName($field->type);
        //echo "create " . $type; 
        return new $type($value, $this, $field, $isFromDB);
    }
    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            throw new \Exception("Field name not valid");
        }
        if ($value instanceof CellType) {
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
        $this->addModified($offset);
        if (!in_array($offset, $this->fields)){
            $this->fields[] = $offset;
        }
        $this->pkCheck();
    }
    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
        return isset($this->cell[$offset]);
    }
    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
        unset($this->fields[array_search($offset, $this->fields) ]);
        unset($this->cell[$offset]);
    }
    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {
        return isset($this->cell[$offset]) ? $this->cell[$offset] : null;
    }
    /**
     * Implements Iterator interface
     * 
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
    }
    /**
     * Implements Iterator interface
     * 
     * @return MiniLab\SelMap\Data\CellTypes\Cell
     */
    public function current()
    {
        $field = $this->fields[$this->position];
        return $this->cell[$field];
    }
    /**
     * Implements Iterator interface
     * 
     * @return MiniLab\SelMap\Data\CellTypes\Cell
     */
    public function key()
    {
        return $this->fields[$this->position];
    }
    /**
     * Implements Iterator interface
     * 
     * @return void
     */
    public function next()
    {
        ++$this->position;
    }
    /**
     * Implements Iterator interface
     * 
     * @return bool
     */
    public function valid()
    {
        return isset($this->fields[$this->position]);
    }
    /**
     * Save current row
     * 
     * @return CellType row PK
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
    /**
     * Return 'true' if the record have primary key
     * 
     * @return boolean
     */
    public function havePK()
    {
        $this->pkCheck();
        $pk = (string)$this->pk;
        return !($pk == "" || $pk == "0");
    }
    /**
     * Insert current row
     * 
     * @return CellType inserted id
     */
    public function insert()
    {
        if ($this->inserted) {
            return $this->pk;
        }
        foreach ($this->cell as $field => $v) {
            foreach ($v->rel as $relName => $fRec) {
                list($tableName, $fKey) = explode(":", $relName);
                 
                if (!isset($this->table->fields[$field]->rel[$relName])) {
                    //throw new \Exception("Can not found relation '" . $relName . "' on table '" . $this->table->name . "' and field '" . $field . "'");
                    continue;
                    // обход бага в нескольких Multirecord на странице
                }

                $relation = $this->table->fields[$field]->rel[$relName];
                if ($relation->inherite ||
                        (($fRec instanceof Record) && $v->value == 0 &&
                                ($relation instanceof Relation) && ($relation->crossRel->isFTableArray()))) {
                    $v->value = $fRec->save()->value;
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
                $values[] = "'" . $v->escapeValue() . "'";
            }
        }
        $query = "INSERT INTO `" . $this->table->name . "` (" . implode(", ", $fields) . ")" . " VALUES (" . implode(", ", $values) . ")";
        $this->db->execNonResult($query);
        $this->inserted = true;
        $this->modified = array();
        if (!$this->havePK()) {
            $this->offsetSet((string)$this->pKeyField, $this->db->insertId());
        }
        if (strval($this->pk) == "0") {
            throw new \Exception("PK is 0. Query: " . $query);
        }
        return $this->pk;
    }
    /**
     * Execute update SQL
     * 
     * @param array $cells
     * @throws \Exception
     * @return boolean
     */
    public function update(array $cells = null)
    {
        if (is_null($cells)) {
            $cells = $this->modified;
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
        $needUpdate = false;
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
            $needUpdate = true;
        }
        if(!$needUpdate) {
            return false;
        }
        $query = substr($query, 0, -2);
        $pk = (string)$this->pKeyField;
        $query.= " WHERE `" . $this->pKeyField . "` = '" . $this->cell[$pk] . "'";
        $this->db->execNonResult($query);
        //echo $query. "<br />";
        $this->modified = array();
        return true;
    }
    /**
     * Implements JsonSerializable
     * 
     * @return array
     */
    public function jsonSerialize()
    {
        $result = $this->cell;
        $result["_pk"] = $this->pk;
        $result["_pKeyField"] = (string)$this->pKeyField;
        return $result;
    }
    /**
     * Always return 'false'
     * 
     * @see \MiniLab\SelMap\Data\DataInterface::isEmpty()
     */
    public function isEmpty()
    {
        return false;
    }
}