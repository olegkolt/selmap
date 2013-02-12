<?php 

namespace MiniLab\SelMap\Query\Where;

use MiniLab\SelMap\Path\Path;
use MiniLab\SelMap\DataBase;
use MiniLab\SelMap\Config\Config;

/**
 * 'OR' or 'AND' statement
 *
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property-read Where $where
 */
class OrAnd {
    protected $type;
    protected $values;
    protected $db;
    protected $where;
    
    public function __construct($type = "AND", DataBase $db, Where $where) {
        $type = strtoupper($type);
        if ($type != "AND" && $type != "OR") {
            throw new \InvalidArgumentException("type must be 'AND' or 'OR'");
        }
        $this->db = $db;
        $this->where = $where;
        $this->type = $type;
        $this->values = array();
    }
    /**
     * @ignore
     */
    public function __get($name){
        if($name == "where"){
            return $this->where;
        }
        throw new \Exception("Property ". $name ." not found");
    }
    /**
     * @ignore
     */
    public function __call($name, $arguments)
    {
        $operators = Config::get("operators");
        preg_match("/^add(\w+)Case$/", $name, $matches);
        if(!isset($matches[1]) || !isset($operators[$matches[1]])) {
            throw new \BadMethodCallException("Method: " . $name . " does not exists");
        }
        return $this->addComparisonCase($operators[$matches[1]], $arguments[0], $arguments[1]);
    }
    /**
     * Get object type: 'OR' or 'AND'
     *
     * @return string type.
     */
    public function getType() {
        return $this->type;
    }
    /**
     * Deprecated. Compatibility reasons 
     * 
     * @param string $mixed Case string
     * return MiniLab\SelMap\Query\Where\OrAnd;
     */
    public function addStringCase($mixed)
    {
        $this->values[] = (string)$mixed;
        return $this;
    }
    /**
     * Create OrAnd object
     *
     * @param string $type 'AND' or 'OR'. 'AND' is default
     * @return MiniLab\SelMap\Query\Where\OrAnd
     */
    public function addOrAnd($type = "AND")
    {
        $orAnd = new OrAnd($type, $this->db, $this->where);
        $this->values[] = $orAnd;
        return $orAnd;
    }
    public function __toString() {
        $output = "";
        $count = count($this->values);
        for ($i = 0;$i < $count;$i++) {
            if ($this->values[$i] instanceof OrAnd) {
                $output.= "(" . $this->values[$i] . ")"; // $this->values[$i]->toString();

            } else {
                $output.= $this->values[$i];
            }
            if ($i < $count - 1) {
                $output.= " " . $this->type . " ";
            }
        }
        return $output;
    }
    public function addIsNullCase(Path $path)
    {
    
    }
    public function addIsNotNullCase(Path $path)
    {
    
    }
    protected function addComparisonCase($operator, $value, Path $path)
    {
        $p = $this->where->map->find($path);
        $this->values[] = "`{" . $path . "}` " . $operator . " " . $value;
        return $this;
    }
}