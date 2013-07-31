<?php
/**
 * This file is part of the SelMap package.
 *
 * (c) Oleg Koltunov <olegkolt@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MiniLab\SelMap;

use MiniLab\SelMap\Model\Table;
use MiniLab\SelMap\Model\TreeTable;
use MiniLab\SelMap\Model\Field;
use MiniLab\SelMap\Model\Relation;
use MiniLab\SelMap\Model\MRelation;
use MiniLab\SelMap\Query\QueryMap;

/**
 * DataBase object
 * 
 * @author Oleg Koltunov <olegkolt@mail.ru>
 *
 */
class DataBase
{
    /**
     * All tables. Array("tableName" => Table)
     * 
     * @var array
     */
    protected $tables;
    /**
     * Connection
     * 
     * @var \mysqli
     */
    protected $conn;
    /**
     * Database name
     * 
     * @var string
     */
    protected $name = "";
    /**
     * Executed queries log. Array("SELECT..", "UPDATE...",..) 
     * 
     * @var array
     */
    protected $queryLog;
    /**
     * Option. 'True' demand to use transactions. Default 'false'
     * 
     * @var bool
     */
    protected $useTransactions = false;
    /**
     * Last opened transaction id. Value increment for each next transaction
     * 
     * @var int
     */
    protected $transactionId;
    /**
     * Namespace for cell types
     * 
     * @var string
     */
    const CELL_TYPES_NAMESPACE = "MiniLab\\SelMap\\Data\\CellTypes\\";
    /**
     * Character encoding
     * 
     * @var string
     */
    const ENCODING = "utf8";
    /**
     * Create a new DataBase
     */
    public function __construct()
    {
        $this->tables = array();
        $this->queryLog = array();
    }
    /**
     * Add new table
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
    public function getTable($tableName)
    {
        if (isset($this->tables[$tableName])) {
            return $this->tables[$tableName];
        }
        throw new \Exception("Table '" . $tableName . "' not found");
    }
    /**
     * Get query log
     *
     * @return array query log.
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }
    /**
     * Use transactions on save. 'true' - use transactions on save; 'false' - not use. Default - 'false'.
     *
     * @param bool $option
     * @return void
     */
    public function setTransactionsOption($option)
    {
        if (is_bool($option)) {
            $this->useTransactions = $option;
        }
    }
    /**
     * Create one to many relation
     * 
     * @param string $table
     * @param string $field
     * @param string $fTable
     * @return void
     */
    public function createOneToManyRel($table, $field, $fTable)
    {
        $t = $this->tables[$table];
        $ft = $this->tables[$fTable];
        $relation = new Relation($t, $field, $ft, $ft->pKeyField);
        $crossRelation = new Relation($ft, $ft->pKeyField, $t, $field);
        $relation->setCrossRelation($crossRelation);
        $crossRelation->setCrossRelation($relation);
        $t->fields[$field]->setRel($fTable . ":" . $ft->pKeyField, $relation);
        $ft->pKeyField->setRel($table . ":" . $field, $crossRelation);
    }
    /**
     * Create inherite (one to one) relation
     * 
     * @param string $table
     * @param string $field
     * @param string $fTable
     * @return void
     */
    public function createInheriteRel($table, $field, $fTable)
    {
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
    /**
     * Create many to many relation
     * 
     * @param string $name
     * @param string $table
     * @param string $key
     * @param string $fTable
     * @param string $fKey
     * @return void
     */
    public function createManyToManyRel($name, $table, $key, $fTable, $fKey)
    {
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
     * @return void
     */
    public function connect($host, $user, $password, $database)
    {
        $this->name = $database;
        $this->conn = new \mysqli($host, $user, $password, $database);
        /* check connection */
        if (mysqli_connect_errno()) {
            $this->mysqlError("Connect failed: " . mysqli_connect_error());
        }
        $this->execNonResult(sprintf("set character_set_client='%s'", self::ENCODING));
        $this->execNonResult(sprintf("set character_set_results='%s'", self::ENCODING));
        $this->execNonResult("set names " . self::ENCODING);
        $this->conn->set_charset(self::ENCODING);
    }
    /**
     * Store executed SQL query
     * 
     * @param string $query
     * @return void
     */
    protected function storeQuery($query)
    {
        $this->queryLog[] = $query;
    }
    /**
     * Execute SQL query. Do not return result
     * 
     * @param string $query SQL query
     * @return void
     */
    public function execNonResult($query)
    {
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
    public function exec($query)
    {
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
    public function startTransaction()
    {
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
     * @return bool
     */
    public function commitTransaction($id)
    {
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
     * @return bool
     */
    public function rollbackTransaction($id)
    {
        if ($this->useTransactions && $this->transactionId == $id) {
            $this->execNonResult("ROLLBACK;");
            $this->transactionId = null;
            return true;
        }
        return false;
    }
    /**
     * Get last instert id
     * 
     * @return int
     */
    public function insertId()
    {
        return $this->conn->insert_id;
    }
    /**
     * Get mysqli connection
     * 
     * @return \mysqli The connection
     */
    public function getConn()
    {
        return $this->conn;
    }
    /**
     * Close db connection
     * 
     * @return void
     */
    public function close()
    {
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
    /**
     * Trow an exception
     * 
     * @param string $query
     * @param string $message
     * @throws \MiniLab\SelMap\DBException
     */
    protected function mysqlError($query, $message)
    {
        $e = new DBException("MySQL query error (" . $message . "): " . $query);
        $e->sql = $query;
        $e->dbMessage = $message;
        throw $e;
    }
}
