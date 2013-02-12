<?php 

namespace MiniLab\SelMap\Query\Where;

use MiniLab\SelMap\Query\QueryMap;

/**
 * SQL WHERE statement analog
 * 
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property      OrAnd    $root Root OrAnd node
 * @property-read QueryMap $map
 */
class Where {
    protected $map;
    protected $root;
    public function __construct(QueryMap $map, OrAnd $root = null) {
        $this->map = $map;
        if (!is_null($root)) {
            $this->root = $root;
        }
    }
    /**
     * @ignore
     */
    public function __get($name){
        if($name == "root" || $name == "map"){
            return $this->$name;
        }
        throw new \Exception("Property ". $name ." not found");
    }
    /**
     * @ignore
     */
    public function __set($name, $value){
        if($name == "root" && $value instanceof OrAnd){
            $this->root = $value;
            return;
        }
        throw new \Exception("Property ". $name ." not found");
    }
    /**
     * Create root OrAnd object
     * 
     * @param string $type 'AND' or 'OR'. 'AND' is default
     * @return MiniLab\SelMap\Query\Where\OrAnd
     */
    public function addOrAnd($type = "AND")
    {
        $this->root = new OrAnd($type, $this->map->db, $this);
        return $this->root;
    }
    public function getSQL() {
        $sql = (string)$this->root;
        return $this->map->queryReadPaths($sql);
    }
}