<?php 

namespace MiniLab\SelMap\Model;


/**
 *
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property-read string $relName Name of the relation table
 * @property-read string $inKey
 * @property-read string $inFKey
 *
 */
class MRelation extends Relation {
    protected $relName;
    protected $tableAlias;
    protected $fTableAlias;
    protected $inKey;
    protected $inFKey;
    public function __construct($relName, Table $table, $key, Table $fTable, $fKey) {
        $this->relName = $relName;
        $this->table = $table;
        $this->key = $key;
        $this->fTable = $fTable;
        $this->fKey = $fKey;
        $this->inKey = $this->table->name . "_" . $key;
        $this->inFKey = $this->fTable->name . "_" . $fKey;
    }
    /**
     * @ignore
     */
    public function __get($name) {
        $props = array("inherite", "inKey", "inFKey", "relName");
        if (in_array($name, $props)) {
            return $this->$name;
        }
        if($name == "crossRel") {
            return $this;
        }
    }
    public function isFTableArray() {
        return true;
    }
    public function setAlias($tableName, $alias) {
        if ($this->table->name == $tableName) {
            $this->tableAlias = $alias;
        } else if ($this->fTable->name == $tableName) {
            $this->fTableAlias = $alias;
        } else {
            throw new \Exception("Incorrect table for relation");
        }
    }
    public function getJoinSql($fTable) {
        if ($fTable == $this->fTable->name) {
            $tAlias = $this->tableAlias;
            $ftAlias = $this->fTableAlias;
            $t = $this->table->name;
            $ft = $this->fTable->name;
            $tKey = $this->table->pKeyField;
            $ftKey = $this->fTable->pKeyField;
            $inKey = $this->inKey;
            $inFKey = $this->inFKey;
        } elseif ($fTable == $this->table->name) {
            $tAlias = $this->fTableAlias;
            $ftAlias = $this->tableAlias;
            $t = $this->fTable->name;
            $ft = $this->table->name;
            $tKey = $this->fTable->pKeyField;
            $ftKey = $this->table->pKeyField;
            $inKey = $this->inFKey;
            $inFKey = $this->inKey;
        }
        return " LEFT JOIN `" . $this->relName . "` ON `" . $tAlias . "`.`" . $tKey . "` = `" . $this->relName . "`.`" . $inKey . "` LEFT JOIN `" . $ft . "` AS `" . $ftAlias . "` ON `" . $ftAlias . "`.`" . $ftKey . "` = `" . $this->relName . "`.`" . $inFKey . "` ";
    }
}