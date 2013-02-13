<?php

namespace MiniLab\SelMap;

use MiniLab\SelMap\Model\Table;
use MiniLab\SelMap\Model\TreeTable;
use MiniLab\SelMap\Model\Field;
use MiniLab\SelMap\Model\Relation;
use MiniLab\SelMap\Model\MRelation;
use MiniLab\SelMap\Query\QueryMap;

/**
 *
 * Enter description here ...
 * @author Oleg Koltunov <olegkolt@mail.ru>
 *
 */
class DataBase {
    protected $tables;
    protected $conn;
    protected $name = "";
    protected $queryLog;
    protected $useTransactions = false;
    protected $transactionId;
    
    const CELL_TYPES_NAMESPACE = "MiniLab\\SelMap\\Data\\CellTypes\\";

    public function __construct() {
        $this->tables = array();
        $this->queryLog = array();
    }
    /**
     * 
     * @param Table $table
     * @return void
     */
    public function addTable(Table $table)
    {
        $this->tables[$table->name] = $table;
    }
    /**
     * Get table object
     *
     * @param string $tableName
     * @return Table Table object
     */
    public function getTable($tableName){
        if(isset($this->tables[$tableName])){
            return $this->tables[$tableName];
        }
        throw new \Exception("Table '" . $tableName . "' not found");
    }
    /**
     * Get query log
     *
     * @return array query log.
     */
    public function getQueryLog(){
        return $this->queryLog;
    }
    /**
     * Use transactions on save. 'true' - use transactions on save; 'false' - not use. Default - 'false'.
     *
     * @param bool $option
     * @return void
     */
    public function setTransactionsOption($option){
        if(is_bool($option)){
            $this->useTransactions = $option;
        }
    }
    public function createOneToManyRel($table, $field, $fTable) {
        $t = $this->tables[$table];
        $ft = $this->tables[$fTable];
        $relation = new Relation($t, $field, $ft, $ft->pKeyField);
        $crossRelation = new Relation($ft, $ft->pKeyField, $t, $field);
        $relation->setCrossRelation($crossRelation);
        $crossRelation->setCrossRelation($relation);
        $t->fields[$field]->setRel($fTable . ":" . $ft->pKeyField, $relation);
        $ft->pKeyField->setRel($table . ":" . $field, $crossRelation);
    }
    public function createInheriteRel($table, $field, $fTable) {
        $t = $this->tables[$table];
        $ft = $this->tables[$fTable];
        $relation = new Relation($t, $field, $ft, $ft->pKeyField);
        $relation->inherite = true;
        $crossRelation = new Relation($ft, $ft->pKeyField, $t, $field);
        //$crossRelation->inherite = true;
        $relation->setCrossRelation($crossRelation);
        $crossRelation->setCrossRelation($relation);
        $t->fields[$field]->setRel($fTable . ":" . $ft->pKeyField, $relation);
        $ft->pKeyField->setRel($table . ":" . $field, $crossRelation);
    }
    public function createManyToManyRel($name, $table, $key, $fTable, $fKey) {
        $t = $this->tables[$table];
        $ft = $this->tables[$fTable];
        $relation = new MRelation($name, $t, $key, $ft, $fKey);
        $t->pKeyField->setRel($fTable . ":" . $fKey, $relation);
        $ft->pKeyField->setRel($table . ":" . $key, $relation);
    }
    /**
     * Connect to database
     * 
     * @param string $host     Database host
     * @param string $user     Database user
     * @param string $password Users Password
     * @param string $database Database name
     */
    public function connect($host, $user, $password, $database) {
        $this->name = $database;
        $this->conn = new \mysqli($host, $user, $password, $database);
        /* check connection */
        if (mysqli_connect_errno()) {
            $this->mysqlError("Connect failed: " . mysqli_connect_error());
        }
        $this->execNonResult("set character_set_client='utf8'");
        $this->execNonResult("set character_set_results='utf8'");
        $this->execNonResult("set names utf8");
        $this->conn->set_charset("utf8");
    }
    protected function storeQuery($query) {
        $this->queryLog[] = $query;
    }
    public function execNonResult($query) {
        $this->storeQuery($query);
        if ($this->conn->query($query) === false) {
            $this->mysqlError($query, $this->conn->error);
        }
    }
    /**
     * Execute SQL query
     * 
     * @param string $query
     * @return mysqli_result
     */
    public function exec($query) {
        $this->storeQuery($query);
        $result = $this->conn->query($query, MYSQLI_USE_RESULT);
        if ($result !== false) {
            return $result;
        }
        $this->mysqlError($query, $this->conn->error);
    }
    /**
     * Start transaction, if transaction has not started
     * 
     * @return int transaction id
     */
    public function startTransaction() {
        if ($this->useTransactions && is_null($this->transactionId)) {
            $this->execNonResult("START TRANSACTION;");
            return ++$this->transactionId;
        }
        return false;
    }
    /**
     * Commit transaction, if transacton id is the last started
     * 
     * @param int $id Transaction id
     *
     * @return bool
     */
    public function commitTransaction($id) {
        if ($this->useTransactions && $this->transactionId == $id) {
            $this->execNonResult("COMMIT;");
            $this->transactionId = null;
            return true;
        }
        return false;
    }
    /**
     * Rollback transaction, if transacton id is the last started
     * 
     * @param int $id Transaction id
     *
     * @return bool
     */
    public function rollbackTransaction($id) {
        if ($this->useTransactions && $this->transactionId == $id) {
            $this->execNonResult("ROLLBACK;");
            $this->transactionId = null;
            return true;
        }
        return false;
    }
    public function insertId() {
        return $this->conn->insert_id;
    }
    /**
     * Get mysqli connection
     * 
     * @return mysqli The connection
     */
    public function getConn() {
        return $this->conn;
    }
    public function close() {
        $this->conn->close();
    }
    /**
     * Create new QueryMap
     * 
     * @return \MiniLab\SelMap\Query\QueryMap
     */
    public function createMap()
    {
        return new QueryMap($this);
    }
    protected function mysqlError($query, $message) {
        $e = new DBException("MySQL query error (" . $message . "): " . $query);
        $e->sql = $query;
        $e->dbMessage = $message;
        throw $e;
    }
}