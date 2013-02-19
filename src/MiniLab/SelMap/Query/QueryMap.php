<?php

namespace MiniLab\SelMap\Query;

use MiniLab\SelMap\Query\Where\Where;
use MiniLab\SelMap\DataBase;
use MiniLab\SelMap\Path\Path;
use MiniLab\SelMap\Path\PathNodeType;
use MiniLab\SelMap\Query\TableNode;

/**
 *
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property      TableNode $root Root TableNode
 * @property-read DataBase  $db
 *
 */
class QueryMap {
    protected $root;
    protected $db;
    protected $queryParams = array();

    /**
     * @ignore
    */
    public function __get($name) {
        $props = array("root", "db");
        if (in_array($name, $props)) {
            return $this->$name;
        }
    }
    /**
     * @ignore
     */
    public function __set($name, $value) {
        switch ($name){
            case "root":
                if($value instanceof TableNode) {
                    $this->root = $value;
                } else {
                    throw new \Exception("Root must be TableNode. " . get_class($value) . " given");
                }
        }
    }
    public function __construct(DataBase $db) {
        $this->db = $db;
    }
    /**
     * Create new Where
     * 
     * @return MiniLab\SelMap\Query\Where\Where
     */
    public function createWhere()
    {
        return new Where($this);
    }
    /**
     * Set map root tableNode
     * 
     * @param string $tableName Name of tableNode
     * @return MiniLab\SelMap\Query\TableNode;
     */
    public function addTable($tableName)
    {
        $this->root = new TableNode((string)$tableName, $this->db, $this);
        return $this->root;
    }
    public function getTableNodeByAlias($aliasName) {
        return $this->queryParams["alias"][$aliasName];
    }
    public function find(Path $path) {
        $path = $path->withoutArrayElements();
        $rec = $this->root;
        foreach ($path as $el) {
            if ($el->getType() == PathNodeType::FIELD) {
                $el = substr($el, 1);
                $rec = $rec->fields[$el];
            } else {
                $rec = $rec->rel[(string)$el];
            }
        }
        return $rec;
    }
    public function getSelectSQL() {
        $this->queryParams["aliasId"] = 0;
        $this->queryParams["alias"] = array();
        $this->queryParams["fieldsSQL"] = "";
        $this->queryParams["joinSQL"] = "";
        $this->queryParams["selectOrder"] = array();
        $this->queryParams["hasBranching"] = false;

        if (!($this->root instanceof TableNode)) {
            throw new \Exception("QueryMap has not load");
        }
        $this->root->selectPrepare($this, $this->queryParams);
        $query = array();
        $query["fields"] = substr($this->queryParams["fieldsSQL"], 0, -2);
        $query["from"] = " FROM `" . $this->root->table->name . "` `" . $this->root->aliasName . "` ";
        $query["join"] = $this->queryParams["joinSQL"];
        $query["hasBranching"] = $this->queryParams["hasBranching"];
        $query["selectOrder"] = $this->queryParams["selectOrder"];
        return $query;
    }
    /**
     * Process SQL code: NodeFields and defined constants
     * 
     * @param  string $sql
     * @return string SQL code
     */
    public function queryReadPaths($sql) {
        // Nodes
        $map = $this;
        $f = function ($m) use ($map) {
            $field = $map->find(new Path($m[1]));
            return "`" . $field->node->aliasName . "`.`" . $field->name . "`";
        };
        $sql = preg_replace_callback("/`\{(.+?)\}`/", $f, $sql);
    
        // Defined constants
        /*$f2 = function ($m) {
            if(defined($m[1])) {
                return constant($m[1]);
            }
            return $m[0];
        };
    
        $sql = preg_replace_callback("/__(\w+?)__/", $f2, $sql);*/
        return $sql;
    }
    protected function isLoadRel() {
        return count($this->root->relations) > 0;
    }
}