<?php

namespace MiniLab\SelMap\Query;

use MiniLab\SelMap\DataBase;
use MiniLab\SelMap\Query\QueryMap;

/**
 *
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property-read string $func SQL funcion string
 *
 */
class NodeFuncField extends NodeField {
    protected $func = "";
    /**
     * Create SQL function field
     * 
     * @param string $name Field alias name
     * @param string $func SQL function
     * @param DataBase $db
     * @param QueryMap
     */
    public function __construct($name, $func, DataBase $db, QueryMap $map) {
        $this->func = $func;
        parent::__construct($name, $db, $map);
    }
    /**
     * @ignore
     */
    public function __get($name) {
        if ($name == "func") {
            return $this->$name;
        }
        return parent::__get($name);
    }

    public function setRel(TableNode $node, $nodeField){
        throw new \Exception("NodeFuncField does not support relations");
    }
}