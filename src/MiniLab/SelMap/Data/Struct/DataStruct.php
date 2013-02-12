<?php

namespace MiniLab\SelMap\Data\Struct;

use MiniLab\SelMap\Path\Path;
use MiniLab\SelMap\Query\QueryMap;
use MiniLab\SelMap\Path\PathNodeType;
use MiniLab\SelMap\Data\Record;
use MiniLab\SelMap\Data\NullCell;
use MiniLab\SelMap\Model\Table;
use MiniLab\SelMap\Query\Where\OrAnd;
use MiniLab\SelMap\Query\Where\Where;
use MiniLab\SelMap\Query\TableNode;

/**
 * @property int $itemsPerPage
 * @property-read array $table
 * @property-read array $row
 * @property-read int   $pagesCount
 * @property-read int   $itemsCount
 */
class DataStruct extends DataStructBase {
    protected $map;
    protected $where;
    protected $rootTableName;
    protected $db;

    protected $table = array();
    protected $row =   array();
    protected $itemsPerPage =  10;
    protected $pagesCount =     0;
    protected $itemsCount =     0;
    /**
     *
     * Create DataStruct
     * @param QueryMap $map
     */
    public function __construct(QueryMap $map) {
        $this->map = $map;
        $this->db = $map->db;
        $this->rootTableName = $this->map->root->table->name;
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
        $props = array("table", "row", "itemsPerPage", "pagesCount", "itemsCount");
        if (in_array($name, $props)) {
            return $this->$name;
        }
    }
    /**
     * Search with respect to the subject. Returns NullCell if seach failed
     * 
     * @param mixed $subject Record, array or Cell
     * @param Path $path
     * @return Cell|Record|array|NullCell
     */
    public static function &search($subject, Path $path) {
        //echo $path . "<br /";
        $rec = & $subject;
        foreach ($path as $el) {
            if($el->getType() == PathNodeType::ARRAYTYPE) {
                $el = (string)$el;
                $next = $rec;
                unset($rec);
                if ($el == "@@current") {
                    if (!($rec = current($next))) {
                        $f = new NullCell();
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
                            if($r[$fld] == $val) {
                                $rec = $next[$k];
                                $f = true;
                                break;
                            }
                        }
                        if(!$f) {
                            $f = new NullCell();
                            return $f;
                        }
                    } else {
                        if (!isset($next[$m]) || !($rec = $next[$m])) {
                            $f = new NullCell();
                            return $f;
                        }
                    }
                }
            } elseif ($el->getType() == PathNodeType::FIELD) {
                $el = substr($el, 1);
                if (!isset($rec[$el])) {
                    $f = new NullCell();
                    return $f;
                }
                $rec = & $rec[$el];
                //echo "cell/";

            } elseif ($el->getType() == PathNodeType::RELATION) {
                if (!isset($rec->rel[(string)$el])) {
                    $f = new NullCell();
                    return $f;
                }
                $rec = & $rec->rel[(string)$el];
                //echo "table/";
            }
        }
        return $rec;
    }
    public function &find(Path $path) {
        if (is_null($this->row)) {
            $f = false;
            return $f;
        }
        $first = $path->first();
        if($first->getType() != PathNodeType::ARRAYTYPE) {
            $newPath = new Path("@@current");
            $path = $newPath->add($path);
        }
        return DataStruct::search($this->row, $path);
    }
    public function setFieldValue($value, Path $path) {
        $field = $path->last();
        if ($field->getType() == PathNodeType::FIELD) {
            $field = substr($field, 1);
        } else {
            throw new \Exception("Path not valid: " . $path);
        }
        if($cell = $this->find($path)) {
            $rec = $cell->record;
            $rec[$field] = $value;
            return $rec[$field];
        }
        if($rec = $this->find($path->withoutLast())) {
            $rec[$field] = $value;
            return $rec[$field];
        }
    }
    /**
     * @see SelMap.DataStructBase::createRecords()
     * @return Record New root record
     */
    public function createRecords() {
        $rec = $this->createRecord($this->map->root->table);
        $this->row[] = $rec;
        $this->createRecordRels($rec, $this->map->root);
        return $rec;
    }
    public function clean() {
        $this->row = array();
        $this->table = array();
    }
    protected function createRecordRels(Record $rec, TableNode $sNode) {
        foreach ($sNode->fields as $field => $v) {
            foreach ($v->rel as $relName => $childNode) {
                list($fTable, $fKey) = explode(":", $relName);
                $relation = $sNode->table->fields[$field]->rel[$relName];
                $rec[$field] = "";
                if (!$childNode->createOneOnInsert && ($relation->isFTableArray() || $relation->crossRel->isFTableArray())) {
                    $rec[$field]->rel[$relName] = array();
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
    protected function createRecord(Table $tbl) {
        $rec = new Record($tbl);
        $this->table[$tbl->name][] = $rec;
        return $rec;
    }
    protected function orderPrepare(array $selectOrder) {
        if (count($selectOrder) == 0) {
            return "";
        }
        ksort($selectOrder);
        return "ORDER BY " . implode(", ", $selectOrder);
    }
    protected function preliminarySelect(array $queryParts, $where, $order, $pageNo, $noSupply = false) {
        $limit = "";
        if ($pageNo !== false) {
            $pageNo--;
            $start = $pageNo * $this->itemsPerPage;
            $limit = "LIMIT " . $start . ", " . $this->itemsPerPage;
        }
        $pKey = (string)$this->map->root->table->pKeyField;
        $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `" . $this->map->root->aliasName . "`.`" . $pKey . "` " . $queryParts["from"] . $queryParts["join"] . $where . " " . $order . " " . $limit;
        if ($result = $this->db->exec($query)) {
            $newWhere = new Where($this->map);
            $or = new OrAnd("OR", $this->db, $newWhere);
            while ($row = $result->fetch_assoc()) {
                $or->addEqualCase($row[$pKey], new Path("@" . $pKey));
            }
            if ($noSupply) {
                $where = "(" . substr($where, 6) . ")";
                $and = new OrAnd($this->db, "AND");
                echo "not ready";
                die();
                $and->addCase($or);
                $and->addCase($where);
                $newWhere->root = $and;
            } else {
                $newWhere->root = $or;
            }
            $result->free();
        } else {
            return "";
        }
        $this->selectFoundRows();
        return "WHERE " . $newWhere->getSQL();
    }
    /**
     * Select one record and relations by record id
     * @param int $id Root table id
     */
    public function selectById($id){
        $pk = $this->map->root->table->pKeyField;
        $this->select(sprintf("WHERE `{@%s}` = '%d'", $pk, $id));
    }
    /**
     *
     * Do selection
     * @param string|Where $where       string or Where
     * @param int          $pageNo      First page is 1
     * @param bool         $countResult
     * @param bool         $noSupply
     */
    public function select($where = "", $pageNo = false, $countResult = false, $noSupply = false) {
        $this->pagesCount = 0;
        $this->itemsCount = 0;
        $limit = "";
        $this->table = array();
        $this->row = array();
        $queryParts = $this->map->getSelectSQL();
        if ($where instanceof Where) {
            $where = "WHERE " . $where->getSQL();
        } else {
            $where = $this->map->queryReadPaths($where);
        }
        $order = $this->orderPrepare($queryParts["selectOrder"]);
        if ($queryParts["hasBranching"] && ($where != "" || $pageNo !== false)) {
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
                    if (!isset($this->table[$name][$pk])) {
                        $rec = new Record($this->db->getTable($name), $tblData);
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
                            $parent->addRel($name, $tableName, $fName, $child, $id);
                            $this->eachNode($child, $sNode, $r);
                        }
                    } else {
                        $parent[$name]->rel[$relName] = array();
                    }
                } else {
                    if (isset($r[$sNode->aliasName])) {
                        $id = $r[$sNode->aliasName];
                        if (isset($this->table[$tableName][$id])) {
                            $child = $this->table[$tableName][$id];
                            $parent->addRel($name, $tableName, $fName, $child);
                            $this->eachNode($child, $sNode, $r);
                        }
                    }
                }
            }
        }
    }
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
        $where = new Where($map);
        $case = new OrAnd($this->db);
        $case->addCase("`{@" . $key . "}` = '" . $value . "'");
        $where->root = $case;
        $ds->select($where);
        $c->rel[$relName] = $ds->row[$value];
    }
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
        $or = new OrAnd($this->db, "OR");
        foreach ($toInsert as $id) {
            $or->addCase("`{@" . $fKey . "}` = " . $id);
            $query = "INSERT INTO `" . $relation->relName . "` (`" . $inField . "`, `" . $inFField . "`) VALUES ('%1', '" . $id . "');";
            $this->onSave[] = function ($ds) use ($query, $path, $db) {
                $id = $ds->find($path)->pk;
                $query = str_replace("%1", $id, $query);
                $db->execNonResult($query);
            };
        }
        $node = $this->map->find($origPath);
        $map = new QueryMap($this->db);
        $map->root = $node;
        $ds = new DataStruct($map);
        $where = new Where($map);
        $where->root = $or;
        $ds->select($where);
        foreach ($ds->row as $id => $row) {
            $records[$id] = $row;
        }
    }
    public function save() {
        //$ov = new \ObjectViewer();
        //$ov->printQueryLog(DB::instance());
        //$ov->printQueryMap($this->map);
        //$ov->printDataStruct($this);
        //echo $ov->getHTML();
        //die();
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