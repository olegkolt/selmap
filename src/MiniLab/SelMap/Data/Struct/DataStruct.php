<?php

namespace MiniLab\SelMap\Data\Struct;

use MiniLab\SelMap\Path\Path;
use MiniLab\SelMap\Query\QueryMap;
use MiniLab\SelMap\Path\PathNodeType;
use MiniLab\SelMap\Data\Record;
use MiniLab\SelMap\Data\RecordSet;
use MiniLab\SelMap\Data\EmptyCell;
use MiniLab\SelMap\Model\Table;
use MiniLab\SelMap\Query\Where\OrAnd;
use MiniLab\SelMap\Query\Where\Where;
use MiniLab\SelMap\Query\TableNode;
use MiniLab\SelMap\Data\DataInterface;

/**
 * @property int $itemsPerPage
 * @property-read array                           $table
 * @property-read array                           $row
 * @property-read int                             $pagesCount
 * @property-read int                             $itemsCount
 * @property-read MiniLab\SelMap\Query\QueryMap   $map
 */
class DataStruct extends DataStructBase {
    /**
     * @var MiniLab\SelMap\Query\QueryMap
     */
    protected $map;
    /**
     * @var MiniLab\SelMap\Query\Where\Where
     */
    protected $where;
    /**
     * @var string
     */
    protected $rootTableName;
    /**
     * @var MiniLab\SelMap\DataBase;
     */
    protected $db;
    /**
     * @var array(MiniLab\SelMap\Data\RecordSet)
     */
    protected $table = array();
    /**
     * @var MiniLab\SelMap\Data\RecordSet
     */
    protected $row;
    /**
     * Count items per page. Item is one root node element - MiniLab\SelMap\Data\Record
     * 
     * @var int
     */
    protected $itemsPerPage =  10;
    /**
     * Last query total pages count
     * 
     * @var int
     */
    protected $pagesCount =     0;
    /**
     * Last query total items count
     * 
     * @var int
     */
    protected $itemsCount =     0;
    /**
     * Create DataStruct
     * 
     * @param QueryMap $map
     */
    public function __construct(QueryMap $map) {
        $this->map = $map;
        $this->db = $map->db;
        $this->rootTableName = $this->map->root->table->name;
        $this->row = new RecordSet();
    }
    /**
     * @ignore
     */
    public function __set($name, $value) {
        if ($name == "itemsPerPage" && is_numeric($value)) {
            $this->itemsPerPage = (int)$value;
            return;
        }
        throw new \Exception("Property or value is not valid");
    }
    /**
     * @ignore
     */
    public function __get($name) {
        $props = array("table", "row", "itemsPerPage", "pagesCount", "itemsCount", "map");
        if (in_array($name, $props)) {
            return $this->$name;
        }
    }
    /**
     * Search with respect to the subject. Returns EmptyCell if seach failed
     * 
     * @param  DataInterface $subject Cell, Record, RecordSet or EmptyCell
     * @param  Path          $path
     * @return DataInterface Cell, Record, RecordSet or EmptyCell
     */
    public static function &search(DataInterface $subject, Path $path) {
        //echo $path . "<br /";
        $rec = & $subject;
        foreach ($path as $el) {
            if($el->getType() == PathNodeType::ARRAYTYPE) {
                $el = (string)$el;
                $next = $rec;
                //unset($rec);
                if ($el == "@@current") {
                    $next->rewind();
                    if (!($rec = $next->current())) {
                    //if (!($rec = current($next))) {
                        $f = new EmptyCell();
                        return $f;
                    }
                } else {
                    $m = trim($el, "[]");
                    $a = explode("=", $m);
                    if(isset($a[1])) {
                        list($fld, $val) = $a;
                        $val = trim($val, "'\"");
                        $f = false;
                        foreach ($next as $k => $r) {
                            if($r[$fld]->value == $val) {
                                $rec = $next[$k];
                                $f = true;
                                break;
                            }
                        }
                        if(!$f) {
                            $f = new EmptyCell();
                            return $f;
                        }
                    } else {
                        if (!isset($next[$m]) || !($rec = $next[$m])) {
                            $f = new EmptyCell();
                            return $f;
                        }
                    }
                }
            } elseif ($el->getType() == PathNodeType::FIELD) {
                $el = substr($el, 1);
                //var_dump($rec);
                //die();
                if ($rec->isEmpty() || !isset($rec[$el])) {
                    $f = new EmptyCell();
                    return $f;
                }
                $rec = & $rec[$el];
                //echo "cell/";

            } elseif ($el->getType() == PathNodeType::RELATION) {
                if (!isset($rec->rel[(string)$el])) {
                    $f = new EmptyCell();
                    return $f;
                }
                $rec = $rec->getRel((string)$el);
            }
        }
        return $rec;
    }
    /**
     * Find relative to DataStruct::$row
     * 
     * @see \MiniLab\SelMap\Data\Struct\DataStructInterface::find()
     */
    public function &find(Path $path) {
        if (is_null($this->row)) {
            $f = new EmptyCell();
            return $f;
        }
        $first = $path->first();
        if($first->getType() != PathNodeType::ARRAYTYPE) {
            $newPath = new Path("@@current");
            $path = $newPath->add($path);
        }
        return DataStruct::search($this->row, $path);
    }
    /**
     * (non-PHPdoc)
     * @see \MiniLab\SelMap\Data\Struct\DataStructInterface::setFieldValue()
     */
    public function setFieldValue($value, Path $path) {
        $field = $path->last();
        if ($field->getType() == PathNodeType::FIELD) {
            $field = substr($field, 1);
        } else {
            throw new \Exception("Path not valid: " . $path);
        }
        $cell = $this->find($path);
        if(!$cell->isEmpty()) {
            $rec = $cell->record;
            $rec[$field] = $value;
            return $rec[$field];
        }
        $rec = $this->find($path->withoutLast());
        $rec[$field] = $value;
        return $rec[$field];
    }
    /**
     * @see \MiniLab\SelMap\Data\Struct\DataStructBase::createRecords()
     * @return Record New root record
     */
    public function createRecords() {
        $rec = $this->createRecord($this->map->root->table);
        $this->row[] = $rec;
        $this->createRecordRels($rec, $this->map->root);
        return $rec;
    }
    /**
     * Clean ->row and ->table property
     * 
     * @return void
     */
    public function clean() {
        $this->row = new RecordSet();
        $this->table = array();
    }
    protected function createRecordRels(Record $rec, TableNode $sNode) {
        foreach ($sNode->fields as $field => $v) {
            foreach ($v->rel as $relName => $childNode) {
                list($fTable, $fKey) = explode(":", $relName);
                $relation = $sNode->table->fields[$field]->rel[$relName];
                $rec[$field] = "";
                if (!$childNode->createOneOnInsert && ($relation->isFTableArray() || $relation->crossRel->isFTableArray())) {
                    $rec[$field]->rel[$relName] = new RecordSet();
                    continue;
                }
                $cRec = $this->createRecord($childNode->table);
                $rec[$field]->rel[$relName] = $cRec;
                // Creating cross rels
                //$cRec[$fKey] = "";
                //$cRec[$fKey]->rel[$sNode->table->name . ":" . $field] = $rec;
                $this->createRecordRels($cRec, $childNode);
            }
        }
    }
    /**
     * 
     * @param MiniLab\SelMap\Model\Table $tbl
     * @return \MiniLab\SelMap\Data\Record
     */
    protected function createRecord(Table $tbl) {
        $rec = new Record($tbl);
        $this->table[$tbl->name][] = $rec;
        return $rec;
    }
    /**
     * 
     * @param array $selectOrder
     * @return string
     */
    protected function orderPrepare(array $selectOrder) {
        if (count($selectOrder) == 0) {
            return "";
        }
        ksort($selectOrder);
        return "ORDER BY " . implode(", ", $selectOrder);
    }
    /**
     * 
     * @param array      $queryParts
     * @param Where|null $where
     * @param string     $order
     * @param int|false  $pageNo
     * @param bool       $noSupply
     * @return null|Where
     */
    protected function preliminarySelect(array $queryParts, $where, $order, $pageNo, $noSupply = false) {
        $limit = "";
        if ($pageNo !== false) {
            $pageNo--;
            $start = $pageNo * $this->itemsPerPage;
            $limit = "LIMIT " . $start . ", " . $this->itemsPerPage;
        }
        $pKey = (string)$this->map->root->table->pKeyField;
        if($where instanceof Where) {
            $sqlWhere = "WHERE " . $where->getSQL();
        } else {
            $sqlWhere = "";
        }
        $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `" . $this->map->root->aliasName . "`.`" . $pKey . "` " . $queryParts["from"] . $queryParts["join"] . $sqlWhere . " " . $order . " " . $limit;
        if ($result = $this->db->exec($query)) {
            $newWhere = $this->map->createWhere();
            $or = $newWhere->addOrAnd("OR");//new OrAnd("OR", $this->db, $newWhere);
            while ($row = $result->fetch_assoc()) {
                $or->addEqualCase($row[$pKey], new Path("@" . $pKey));
            }
            if ($noSupply) {
                //$where = "(" . substr($where, 6) . ")";
                $and = new OrAnd("AND", $this->db, $where);
                $and->addCase($or);
                $and->addCase($where->root);
                $newWhere->root = $and;
            } else {
                $newWhere->root = $or;
            }
            $result->free();
        } else {
            return null;
        }
        $this->selectFoundRows();
        return $newWhere;
    }
    /**
     * Select one record and relations by record id
     * 
     * @param int $id Root table id
     */
    public function selectById($id){
        $pk = $this->map->root->table->pKeyField;
        $where = $this->map->createWhere()->addOrAnd()->addEqualCase($id, new Path("@" . $pk))->where;
        $this->select($where);
    }
    /**
     * Do selection
     * 
     * @param Where|null $where
     * @param int|false  $pageNo      First page is 1. Default 'false'
     * @param bool       $countResult Default 'false'
     * @param bool       $noSupply    Default 'false'
     * @return void
     */
    public function select($where = null, $pageNo = false, $countResult = false, $noSupply = false) {
        $this->pagesCount = 0;
        $this->itemsCount = 0;
        $limit = "";
        $this->table = array();
        $this->row = new RecordSet();
        $queryParts = $this->map->getSelectSQL();
        
        if (!($where instanceof Where) && !is_null($where)) {
            throw new \InvalidArgumentException("\$where must be instance of 'Where' or null");
        }
        if($pageNo !== false && $pageNo < 1) {
            throw new \InvalidArgumentException("\$pageNo must be greater than 0 or false");
        }
        
        $order = $this->orderPrepare($queryParts["selectOrder"]);
        if ($queryParts["hasBranching"] && (!is_null($where) || $pageNo !== false)) {
            $where = $this->preliminarySelect($queryParts, $where, $order, $pageNo, $noSupply);
            if ($this->itemsCount == 0) {
                return;
            }
        } elseif ($pageNo !== false) {
            $countResult = true;
            $pageNo--;
            $start = $pageNo * $this->itemsPerPage;
            $limit = "LIMIT " . $start . ", " . $this->itemsPerPage;
        }
        if($where instanceof Where) {
            $where = "WHERE " . $where->getSQL();
        } else {
            $where = "";
        }
        $query = "SELECT ";
        if ($countResult) {
            $query.= "SQL_CALC_FOUND_ROWS ";
        }
        $query.= $queryParts["fields"] . $queryParts["from"] . $queryParts["join"] . " " . $where . " " . $order . " " . $limit;
        if ($result = $this->db->exec($query)) {
            $cr = array();
            while ($row = $result->fetch_assoc()) {
                $currData = array();
                foreach ($row as $col => $value) {
                    list($alias, $field) = explode(".", $col);
                    $currData[$alias][$field] = $value;
                }
                $c = array();
                $continue = true;
                foreach ($currData as $alias => $tblData) {
                    $name = $this->map->getTableNodeByAlias($alias)->table->name;
                    $pKeyField = (string)$this->db->getTable($name)->pKeyField;
                    if (!isset($tblData[$pKeyField])) {
                        continue;
                    }
                    $pk = $tblData[$pKeyField];
                    $c[$alias] = $pk;
                    $continue = false;
                    if (!isset($this->table[$name])) {
                        $this->table[$name] = new RecordSet();
                    }
                    if (!isset($this->table[$name][$pk])) {
                        $rec = new Record($this->db->getTable($name), $tblData, true);
                        $this->table[$name][$pk] = $rec;
                    }
                }
                if (!$continue) {
                    $cr[] = $c;
                }
            }
            $result->free();
            $this->createSelObjects($cr);
            if ($countResult && $this->pagesCount == 0 && $this->itemsCount == 0) {
                $this->selectFoundRows();
            }
            //$this->row->rewind();
        }
    }
    protected function selectFoundRows() {
        $query = "SELECT FOUND_ROWS();";
        $result = $this->db->exec($query);
        $row = $result->fetch_row();
        $this->itemsCount = (int)$row[0];
        $this->pagesCount = ceil($this->itemsCount / $this->itemsPerPage);
        $result->free();
    }
    protected function createSelObjects($cr) {
        foreach ($cr as $r) {
            $rootRowId = $r[$this->map->root->aliasName];
            if (!isset($this->row[$rootRowId])) {
                $this->row[$rootRowId] = $this->table[$this->rootTableName][$rootRowId];
            }
            $this->eachNode($this->row[$rootRowId], $this->map->root, $r);
        }
    }
    protected function eachNode(Record $parent, TableNode $mapNode, array $r) {
        foreach ($mapNode->fields as $name => $field) {
            foreach ($field->rel as $relName => $sNode) {
                list($tableName, $fName) = explode(":", $relName);
                $rel = $parent->table->fields[$name]->rel[$relName];
                if ($rel->isFTableArray()) {
                    //if ($rel->crossRel->isFTableArray()) {
                    if (isset($r[$sNode->aliasName])) {
                        $id = $r[$sNode->aliasName];
                        if (isset($this->table[$tableName][$id])) {
                            $child = $this->table[$tableName][$id];
                            $parent[$name]->addMultipleRel($relName, $child, $id);
                            //$parent->addRel($name, $tableName, $fName, $child, $id);
                            $this->eachNode($child, $sNode, $r);
                        }
                    } else {
                        $parent[$name]->rel[$relName] = new RecordSet();
                    }
                } else {
                    if (isset($r[$sNode->aliasName])) {
                        $id = $r[$sNode->aliasName];
                        if (isset($this->table[$tableName][$id])) {
                            $child = $this->table[$tableName][$id];
                            if(!isset($parent[$name])) {
                                $parent[$name] = $id;
                            }
                            $parent[$name]->addSingleRel($relName, $child);
                            //$parent->addRel($name, $tableName, $fName, $child);
                            $this->eachNode($child, $sNode, $r);
                        }
                    }
                }
            }
        }
    }
    /**
     * (non-PHPdoc)
     * @see \MiniLab\SelMap\Data\Struct\DataStructInterface::setOneToManyRelation()
     */
    public function setOneToManyRelation($value, Path $path) {
        $relName = (string)$path->last();
        $aPath = $path->withoutLast();
        $c = $this->find($aPath);
        if (is_null($value) || $value == "") {
            $c->value = $value;
            unset($c->rel[$relName]);
            return;
        }
        if (isset($c->value) && $c->value == $value) {
            return;
        }
        if (!isset($c->value)) {
            $c = $this->setFieldValue($value, $aPath);
        } else {
            $c->value = $value;
        }
        list($t, $key) = explode(":", $relName);
        $node = $this->map->find($path->withoutArrayElements());
        $map = new QueryMap($this->db);
        $map->root = $node;
        $ds = new DataStruct($map);
        $where = $map->createWhere()->addOrAnd()->addEqualCase($value, new Path("@" . $key))->where;
        $ds->select($where);
        $c->rel[$relName] = $ds->row[$value];
    }
    /**
     * Set many to many relations
     * 
     * @param array $newValues New foreign records
     * @param Path $path
     */
    public function setManyToManyRelation(array $newValues, Path $path) {
        if ($records = & $this->find($path)) {
            $current = array_keys($records);
        } else {
            $current = array();
        }
        $toInsert = array_diff($newValues, $current);
        $toDelete = array_diff($current, $newValues);
        if (count($toInsert) == 0 && count($toDelete) == 0) {
            return;
        }
        $origPath = clone $path;
        list($fTable, $fKey) = explode(":", $path->last());
        $path = $path->withoutLast();
        $key = substr($path->last(), 1);
        $path = $path->withoutLast();
        if (count($path->asArray()) == 0) {
            $table = $this->map->root->table->name;
        } else {
            list($table) = explode(":", $path->last());
        }
        $relation = $this->db->tables[$table]->fields[$key]->rel[$fTable . ":" . $fKey];
        if ($relation->table->name == $table) {
            $inField = $relation->inKey;
            $inFField = $relation->inFKey;
        } else if ($relation->fTable->name == $table) {
            $inField = $relation->inFKey;
            $inFField = $relation->inKey;
        }
        $db = $this->db;
        foreach ($toDelete as $id) {
            unset($records[$id]);
            $query = "DELETE FROM `" . $relation->relName . "` WHERE `" . $inField . "` = '%1' AND `" . $inFField . "` = '" . $id . "';";
            $this->onSave[] = function ($ds) use ($query, $path, $db) {
                $id = $ds->find($path)->pk;
                $query = str_replace("%1", $id, $query);
                $db->execNonResult($query);
            };
        }
        if (count($toInsert) == 0) {
            return;
        }
        
        $node = $this->map->find($origPath);
        $map = $this->db->createMap();
        $map->root = $node;
        $ds = new DataStruct($map);
        $where = $map->createWhere()->addOrAnd("OR")->where;
        foreach ($toInsert as $id) {
            $where->root->addEqualCase($id, new Path("@" . $fKey));
            $query = "INSERT INTO `" . $relation->relName . "` (`" . $inField . "`, `" . $inFField . "`) VALUES ('%1', '" . $id . "');";
            $this->onSave[] = function ($ds) use ($query, $path, $db) {
                $id = $ds->find($path)->pk;
                $query = str_replace("%1", $id, $query);
                $db->execNonResult($query);
            };
        }
        $ds->select($where);
        foreach ($ds->row as $id => $row) {
            $records[$id] = $row;
        }
    }
    /**
     * (non-PHPdoc)
     * @see \MiniLab\SelMap\Data\Struct\DataStructBase::save()
     */
    public function save() {
        $tId = $this->db->startTransaction();
        foreach ($this->table as $tbl) {
            foreach ($tbl as $rec) {
                $rec->save();
            }
        }
        parent::save();
        $this->db->commitTransaction($tId);
    }
}