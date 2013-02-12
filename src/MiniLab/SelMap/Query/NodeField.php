<?php

namespace MiniLab\SelMap\Query;

use MiniLab\SelMap\Query\TableNode;
use MiniLab\SelMap\DataBase;
use MiniLab\SelMap\Query\QueryMap;

/**
 *
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property-read string    $name     Field name or alias
 * @property-read TableNode $node     TableNode of this field
 * @property-read QueryMap  $map      Owner QueryMap
 * @property-read array     $rel      Relations
 * @property      string    $order    Order direction: ASC or DESC
 * @property      int       $orderPos Sort priority
 */
class NodeField {
    protected $order = "ASC";
    protected $orderPos;
    protected $name;
    protected $db;
    protected $map;
    protected $node;
    protected $rel = array();
    /**
     * Create table field
     * 
     * @param string   $name Field name
     * @param DataBase $db
     * @param QueryMap
     */
    public function __construct($name, DataBase $db, QueryMap $map) {
        $this->name = (string)$name;
        $this->db = $db;
        $this->map = $map;
    }
    /**
     * @ignore
     */
    public function __get($name) {
        $props = array("order", "orderPos", "name", "node", "rel", "map");
        if (in_array($name, $props)) {
            return $this->$name;
        }
    }
    /**
     * @ignore
     */
    public function __set($name, $value) {
        if($name == "order") {
            $this->setOrder($value);
        }elseif($name == "orderPos") {
            $this->setOrderPos($value);
        } else {
            throw new \Exception("Can not write property " . $name);
        }
    }
    public function setTableNode(TableNode $node) {
        $this->node = $node;
    }
    /**
     * 
     * @param string $tableName
     * @param string $fielName
     * @return TableNode
     */
    public function addTable($tableName, $fieldName)
    {
        $tableName = (string)$tableName;
        $fieldName = (string)$fieldName;
        $node = new TableNode($tableName, $this->db, $this->map);
        $this->setRel($node, $fieldName);
        $node->setParent($this);
        return $node;
    }
    /**
     * Set relation to the field
     * 
     * @param TableNode $node      Related node
     * @param string    $nodeField Related node field name
     * @return TableNode
     */
    public function setRel(TableNode $node, $nodeField) {
        $nodeField = (string)$nodeField;
        $fTable = $node->table->name;
        $this->rel[$fTable . ":" . $nodeField] = $node;
        $node->addField($nodeField);
        return $node;
    }
    /**
     * Determines the sorting order
     * 
     * @param string $orderType Only ASC or DESC is valid
     * @throws \InvalidArgumentException
     * @return NodeField
     */
    public function setOrder($orderType = "ASC")
    {
        $orderType = strtoupper($orderType);
        if($orderType == "ASC" || $value = "DESC") {
            $this->order = $orderType;
        } else {
            throw new \InvalidArgumentException("Incorrect value type for order property. Only 'ASC' or 'DESC' is possible");
        }
        return $this;
    }
    /**
     * Determines the significance of the field to sort. 0 - the most significant field
     * 
     * @param int $position Positive int The field significance
     * @return NodeField
     */
    public function setOrderPos($position)
    {
        $pos = (int)$position;
        if($pos < 0) {
            $pos = 0;
        }
        $this->orderPos = $pos;
        return $this;
    }
}