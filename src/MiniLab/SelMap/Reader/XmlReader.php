<?php

namespace MiniLab\SelMap\Reader;

use MiniLab\SelMap\Query\Where\Where;
use MiniLab\SelMap\Query\Where\OrAnd;
use MiniLab\SelMap\Query\QueryMap;
use MiniLab\SelMap\Query\TableNode;
use MiniLab\SelMap\DataBase;
use MiniLab\SelMap\Path\Path;
use MiniLab\SelMap\Model\Table;
use MiniLab\SelMap\Model\TreeTable;
use MiniLab\SelMap\Model\Field;

class XmlReader
{
    /**
     * @var DataBase
     */
    protected $db;
    /**
     * 
     * @param DataBase $db
     */
    public function __construct(DataBase $db)
    {
        $this->db = $db;
    }
    /**
     * Read XML file. Return root element
     * 
     * @param string $filePath
     * @return DOMElement
     */
    public static function readXmlFile($filePath)
    {
        $xMap = new \DOMDocument();
        $xMap->load($filePath);
        return $xMap->documentElement;
    }
    /**
     * Read SelMap schema
     * 
     * @param \DOMElement $xml
     * @throws \Exception
     * @return void
     */
    public function readSchema(\DOMElement $xml)
    {
        $xTableList = $xml->getElementsByTagName("Table");
        foreach ($xTableList as $xTable) {
            $tableName = $xTable->getAttribute("Name");
            $pKeyFieldName = null;
            $treeKeyFieldName = null;
            $fields = array();
            foreach ($xTable->childNodes as $xField) {
                if ($xField instanceof \DOMElement) {
                    $currentField = (string)$xField->getAttribute("Name");
                    $nullable = false;
                    if($xField->hasAttribute("Null")) {
                        $attr = strtolower($xField->getAttribute("Null"));
                        if($attr == "true") {
                            $nullable = true;
                        }
                    }
                    $field = new Field($currentField, $nullable);
                    $fields[$currentField] = $field;
                    if ($xField->hasAttribute("PKey")) {
                        $pKeyFieldName = $currentField;
                    }
                    if ($xField->hasAttribute("TreeKey")) {
                        $treeKeyFieldName = $currentField;
                    }
                }
            }
            if(is_null($pKeyFieldName)){
                throw new \Exception("No PK found for table '" . $tableName . "'");
            }
            if(!is_null($treeKeyFieldName)) {
                $table = new TreeTable($this->db, $tableName, $fields, $pKeyFieldName, $treeKeyFieldName);
            } else {
                $table = new Table($this->db, $tableName, $fields, $pKeyFieldName);
            }
            $this->db->addTable($table);
        }
        foreach ($xTableList as $xTable) {
            $tableName = $xTable->getAttribute("Name");
            foreach ($xTable->childNodes as $xField) {
                if ($xField instanceof \DOMElement) {
                    $currentField = (string)$xField->getAttribute("Name");
                    if ($xField->hasAttribute("FTable")) {
                        $fTableName = $xField->getAttribute("FTable");
                        $this->db->createOneToManyRel($tableName, $currentField, $fTableName);
                    }
                    if ($xField->hasAttribute("Inherite")) {
                        $fTableName = $xField->getAttribute("Inherite");
                        $this->db->createInheriteRel($tableName, $currentField, $fTableName);
                    }
                    if ($xField->hasAttribute("Type")) {
                        $type = $xField->getAttribute("Type");
                        $this->db->getTable($tableName)->fields[$currentField]->setType($type);
                    }
                }
            }
        }
        $xRelationsList = $xml->getElementsByTagName("RelTable");
        foreach ($xRelationsList as $xRelation) {
            $relName = $xRelation->getAttribute("Name");
            $table = $xRelation->getAttribute("Table");
            $key = $xRelation->getAttribute("Key");
            $fTable = $xRelation->getAttribute("FTable");
            $fKey = $xRelation->getAttribute("FKey");
            $this->db->createManyToManyRel($relName, $table, $key, $fTable, $fKey);
        }
    }
    /**
     * 
     * @param \DOMElement $domNode
     * @return MiniLab\SelMap\Query\QueryMap
     */
    public function readMap(\DOMElement $domNode)
    {
        $map = $this->db->createMap();
        foreach ($domNode->childNodes as $xChild) {
            if ($xChild instanceof \DOMElement) {
                $this->createNode($xChild, $map->root, false, $map);
                break;
            }
        }
        return $map;
    }
    /**
     * Process <Table> XML element
     * 
     * @param \DOMElement    $xNode
     * @param TableNode|null $sNode
     * @param string|bool    $fieldName QueryMap if root call, alse string
     * @param QueryMap       $map
     */
    protected function createNode(\DOMElement $xNode, $sNode, $fieldName, QueryMap $map)
    {
        $name = $xNode->getAttribute("Name");
        if ($fieldName === false) {
            $currentNode = $map->addTable($name);
        } else {
            $fTableField = $xNode->getAttribute("On");
            $currentNode = $sNode->fields[$fieldName]->addTable($name, $fTableField);
            //$sNode->
        }
        if ($xNode->hasAttribute("OnInsert")) {
            if ($xNode->getAttribute("OnInsert") == "CreateOne") {
                $currentNode->createOneOnInsert = true;
            }
        }
        foreach ($xNode->childNodes as $xChild) {
            if ($xChild instanceof \DOMElement && $xChild->tagName == "Field") {
                if ($xChild->hasAttribute("Name")) {
                    $name = $xChild->getAttribute("Name");
                    $nodeField = $currentNode->addField($name);
                } else if ($xChild->hasAttribute("Alias") && $xChild->hasAttribute("Func")) {
                    $alias = $xChild->getAttribute("Alias");
                    $func = $xChild->getAttribute("Func");
                    $nodeField = $currentNode->addFuncField($alias, $func);
                }
                if ($xChild->hasAttribute("Order")) {
                    $pos = $xChild->getAttribute("Order");
                    $nodeField->orderPos = $pos;
                }
                if ($xChild->hasAttribute("OrderDesc")) {
                    $pos = $xChild->getAttribute("OrderDesc");
                    $nodeField->order = "DESC";
                    $nodeField->orderPos = $pos;
                }
                foreach ($xChild->childNodes as $xc) {
                    if ($xc instanceof \DOMElement && $xc->tagName == "Table") {
                        $this->createNode($xc, $currentNode, $name, $map);
                    }
                }
            }
        }
    }
    /**
     * Read where element
     * 
     * @param QueryMap $map
     * @param \DOMNode $domNode
     * @return MiniLab\SelMap\Query\Where\Where
     */
    public function readWhere(QueryMap $map, \DOMNode $domNode)
    {
        $where = new Where($map);
        $xRoot = $domNode->getElementsByTagName("*")->item(0);
        $where->root = new OrAnd($xRoot->tagName, $map->db, $where);
        $this->readCase($xRoot, $where->root);
        return $where;
    }
    /**
     * Read each OrAnd node
     * 
     * @param \DOMNode $case
     * @param OrAnd $sCase
     */
    protected function readCase(\DOMNode $case, OrAnd $sCase)
    {
        $childs = $case->childNodes;
        for ($i = 0;$i < $childs->length;$i++) {
            if (!($childs->item($i) instanceof \DOMElement)) {
                continue;
            }
            $child = $childs->item($i);
            if ($child->tagName == "OR" || $child->tagName == "AND") {
                $this->readCase($child, $sCase->addOrAnd($child->tagName));
            } else {
                $method = "add" . $child->tagName;
                $path = new Path($child->getAttribute("Path"));
                if($child->hasAttribute("Value")) {
                    $value = $child->getAttribute("Value");
                    $sCase->$method($value, $path);
                } else {
                    $sCase->$method($path);
                }
            }
        }
        //return $sCase;
    }
}