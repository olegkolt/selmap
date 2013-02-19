<?php

namespace MiniLab\SelMap\Query;

use MiniLab\SelMap\DataBase;
use MiniLab\SelMap\Query\NodeField;
use MiniLab\SelMap\Query\NodeFuncField;
use MiniLab\SelMap\Query\QueryMap;
use MiniLab\SelMap\Model\MRelation;

/**
 * Enter description here ...
 * 
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property-read Table     $table
 * @property-read array     $fields
 * @property-read QueryMap  $map
 * @property-read NodeField $parent Parent field. Null if node is root
 * @property      bool      $createOneOnInsert
 */
class TableNode {
    protected $fields;
    protected $table;
    protected $isPKeySet = false;
    protected $db;
    protected $map;
    protected $parent;
    protected $aliasName;
    protected $createOneOnInsert = false;
    public function __construct($tableName, DataBase $db, QueryMap $map) { /*  array $fields = null */
        $this->map = $map;
        $this->db = $db;
        $this->table = $this->db->getTable($tableName);
        $this->fields = array();
        /*if (is_array($fields) && count($fields) == 0) {
            $fields = $this->table->fields;
        }
        if (is_null($fields)) {
            $fields = array();
        }
        foreach ($fields as $f) {
            $this->addField($f);
        }*/
        if (!$this->isPKeySet) {
            $this->addField((string)$this->table->pKeyField);
        }
    }
    public function __get($name) {
        $props = array("fields", "table", "createOneOnInsert", "aliasName", "map", "parent");
        if (in_array($name, $props)) {
            return $this->$name;
        }
    }
    public function __set($name, $value) {
        if($name == "createOneOnInsert") {
            $this->setCreateOnInsert($value);
            return;
        }
        throw new \Exception("Property '" . $name . "' not found");
    }
    public function setParent(NodeField $field)
    {
        $this->parent = $field;
    }
    public function selectPrepare(QueryMap $map, array &$queryParams) {
        $this->aliasName = "t" . $queryParams["aliasId"]++;
        $queryParams["alias"][$this->aliasName] = $this;
        foreach ($this->fields as $name => $f) {
            if ($f instanceof NodeFuncField) {
                $sqlFunc = $f->func;
                $sqlFunc = $map->queryReadPaths($sqlFunc);
                $queryParams["fieldsSQL"].= $sqlFunc . " AS '" . $this->aliasName . "." . $f->name . "', ";
            } else {
                $queryParams["fieldsSQL"].= "`" . $this->aliasName . "`.`" . $name . "` AS '" . $this->aliasName . "." . $name . "', ";
                foreach ($f->rel as $relName => $tableNode) {
                    list($tName, $fName) = explode(":", $relName);
                    $tableNode->selectPrepare($map, $queryParams);
                    $rel = $this->table->fields[$name]->rel[$relName];
                    if (!$queryParams["hasBranching"]) {
                        $queryParams["hasBranching"] = $rel->crossRel->isFTableArray();
                    }
                    if ($rel instanceof MRelation) {
                        $rel->setAlias($tName, $tableNode->aliasName);
                        $rel->setAlias($this->table->name, $this->aliasName);
                        $queryParams["joinSQL"] = $rel->getJoinSql($tName) . $queryParams["joinSQL"];
                    } else {
                        $queryParams["joinSQL"] = " LEFT JOIN `" . $tName . "` AS `" .
                                $tableNode->aliasName . "` ON `" . $this->aliasName . "`.`" . $name . "` = `" .
                                $tableNode->aliasName . "`.`" . $rel->fKey . "` " .
                                $queryParams["joinSQL"];
                    }
                }
            }
            if (!is_null($f->orderPos)) {
                $o = "`" . $this->aliasName . "`.`" . $name . "` " . $f->order;
                $queryParams["selectOrder"][(int)$f->orderPos] = $o;
            }
        }
    }
    public function getJoinSQL() {
        $joinSQL = " ";
        foreach ($this->relations as $fTableName => $fTableMap) {
            $fTable = $fTableMap->table->name;
            $rel = $this->table->relations[$fTable];
            $joinSQL.= $rel->getJoinSql($fTableName) . $fTableMap->getJoinSQL();
        }
        return $joinSQL;
    }
    /**
     * Add NodeField field to TabelNode
     * 
     * @param string $fieldName The field name
     * @return NodeField
     */
    public function addField($fieldName)
    {
        $fieldName = (string)$fieldName;
        $node = new NodeField($fieldName, $this->db, $this->map);
        $node->setTableNode($this);
        $this->fields[$node->name] = $node;
        if ($node->name == $this->table->pKeyField) {
            $this->isPKeySet = true;
        }
        return $node;
    }
    /**
     * Add NodeFuncField field to TabelNode
     *
     * @param string $name Field alias name
     * @param string $func SQL function
     * @return NodeFuncField
     */
    public function addFuncField($aliasName, $func)
    {
        $aliasName = (string)$aliasName;
        $func = (string)$func;
        $node = new NodeFuncField($aliasName, $func, $this->db, $this->map);
        $node->setTableNode($this);
        $this->fields[$node->name] = $node;
        return $node;
    }
    /**
     * Add all fields into TableNode
     * 
     * @return TableNode 
     */
    public function addAllFields()
    {
        foreach ($this->table->fields as $f) {
            $this->addField($f);
        }
        return $this;
    }
    /**
     * Set createOnInsert option
     *
     * @param  bool $value
     * @return TableNode
     */
    public function setCreateOnInsert($value)
    {
        $this->createOneOnInsert = (bool)$value;
        return $this;
    }
}