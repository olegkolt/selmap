<?php

namespace MiniLab\SelMap\Data\Struct;

use MiniLab\SelMap\Query\QueryMap;
use MiniLab\SelMap\Model\TreeTable;
use MiniLab\SelMap\Path\Path;

/**
 * Proccess tree relations
 * 
 * @author Oleg Koltunov <olegkolt@mail.ru>
 */
class TreeDataStruct extends DataStruct
{
    public function __construct(QueryMap $map)
    {
        if(!($map->root->table instanceof TreeTable)) {
            throw new \Exception("Root map table must have tree relation");
        }
        parent::__construct($map);
    }
    /**
     * (non-PHPdoc)
     * @see \MiniLab\SelMap\Data\Struct\DataStruct::select()
     */
    public function select($where = null, $pageNo = false, $countResult = false, $noSupply = false)
    {
        $parentField = $this->map->root->table->parentField->name;
        $field = $this->map->root->fields[$parentField];
        $field->orderPos = 0;
        parent::select($where, $pageNo, $countResult, $noSupply);
        $idField = $this->map->root->table->pKeyField->name;

        $rootTable = $this->map->root->table->name;
        $relName = $rootTable . ":" . $parentField;
        foreach ($this->row as $id => $row) {
            $parentId = $row[$parentField]->value;
            if(!is_null($parentId)) {
                $this->table[$rootTable][$parentId][$idField]->addMultipleRel($relName, $row, $id);
                //var_dump($this->table[$rootTable][$parentId]); 
                //die();
                //unset($this->row[$id]);
            }
        }
    }
    /**
     * Get Path object from simple tree path
     * 
     * @param string $fieldName
     * @param string $path
     * @return Path
     */
    public function transformPath($fieldName, $path)
    {
        $parentField = $this->map->root->table->parentField->name;
        $idField = $this->map->root->table->pKeyField->name;
        $rootTable = $this->map->root->table->name;
        $relName = $rootTable . ":" . $parentField;
        $path = explode("/", $path);
        $newPath = array();
        foreach($path as $el) {
            $newPath[] = "@" . $idField . "/" . $relName . "/[" . $fieldName . "='" . $el . "']";
        }
        return new Path(implode("/", $newPath));
    }
}