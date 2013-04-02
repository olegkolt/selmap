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
 * @method OrAnd addEqualCase(mixed $value, Path $path) Add case like: `field` = 'value'
 * @method OrAnd addNotEqualCase(mixed $value, Path $path) Add case like: `field` != 'value'
 * @method OrAnd addLikeCase(mixed $value, Path $path) Add case like: `field` LIKE 'value'
 * @method OrAnd addNotLikeCase(mixed $value, Path $path) Add case like: `field` NOT LIKE 'value'
 * @method OrAnd addGreaterCase(mixed $value, Path $path) Add case like: `field` > 'value'
 * @method OrAnd addGreaterOrEqualCase(mixed $value, Path $path) Add case like: `field` >= 'value'
 * @method OrAnd addLessCase(mixed $value, Path $path) Add case like: `field` < 'value'
 * @method OrAnd addLessOrEqualCase(mixed $value, Path $path) Add case like: `field` <= 'value'
 * @method OrAnd addEqualCase(Path $path, Path $contrPath) Add case like: `field` = `field`
 * @method OrAnd addNotEqualCase(Path $path, Path $contrPath) Add case like: `field` != `field`
 * @method OrAnd addLikeCase(Path $path, Path $contrPath) Add case like: `field` LIKE `field`
 * @method OrAnd addNotLikeCase(Path $path, Path $contrPath) Add case like: `field` NOT LIKE `field`
 * @method OrAnd addGreaterCase(Path $path, Path $contrPath) Add case like: `field` > `field`
 * @method OrAnd addGreaterOrEqualCase(Path $path, Path $contrPath) Add case like: `field` >= `field`
 * @method OrAnd addLessCase(Path $path, Path $contrPath) Add case like: `field` < `field`
 * @method OrAnd addLessOrEqualCase(Path $path, Path $contrPath) Add case like: `field` <= `field`
 * 
 */
class OrAnd
{
    /**
     * OrAnd type
     * 
     * @var string "AND" or "OR"
     */
    protected $type;
    /**
     * Array of OrAnd objects or values
     * 
     * @var array
     */
    protected $values;
    /**
     * DataBase instance
     * 
     * @var MiniLab\SelMap\DataBase
     */
    protected $db;
    /**
     * Where owner
     * 
     * @var MiniLab\SelMap\Query\Where\Where
     */
    protected $where;
    /**
     * Create a new OrAnd
     * 
     * @param string   $type
     * @param DataBase $db
     * @param Where    $where
     * @throws \InvalidArgumentException Type must be 'AND' or 'OR'
     */
    public function __construct($type = "AND", DataBase $db, Where $where)
    {
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
    public function __get($name)
    {
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
        preg_match("/^add(Date|)(\w+)Case$/", $name, $matches);
        if(!isset($matches[2]) || !isset($operators[$matches[2]])) {
            throw new \BadMethodCallException("Method: " . $name . " does not exists");
        }
        if($matches[1] == "") {
            if($arguments[0] instanceof Path){
                return $this->addContrComparisonCase($operators[$matches[2]], $arguments[0], $arguments[1]);
            }
            return $this->addComparisonCase($operators[$matches[2]], $arguments[0], $arguments[1]);
        }
        if($arguments[0] instanceof Path){
            return $this->addDateContrComparisonCase($operators[$matches[2]], $arguments[0], $arguments[1]);
        }
        return $this->addDateComparisonCase($operators[$matches[2]], $arguments[0], $arguments[1]);
    }
    /**
     * Get object type: 'OR' or 'AND'
     *
     * @return string type.
     */
    public function getType()
    {
        return $this->type;
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
    /**
     * Add OrAnd instance
     * 
     * @param OrAnd $orAnd
     * @return OrAnd
     */
    public function addOrAndInstance(OrAnd $orAnd)
    {
        $this->values[] = $orAnd;
        return $orAnd;
    }
    /**
     * Get SQL string
     * 
     * @return string
     */
    public function __toString()
    {
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
    /**
     * Add IS NULL case: `field` IS NULL
     * 
     * @param Path $path
     * @return \MiniLab\SelMap\Query\Where\OrAnd
     */
    public function addIsNullCase(Path $path)
    {
        $this->values[] = "`{" . $path . "}` IS NULL";
        return $this;
    }
    /**
     * Add IS NOT NULL case: `field` IS NOT NULL
     * 
     * @param Path $path
     * @return \MiniLab\SelMap\Query\Where\OrAnd
     */
    public function addIsNotNullCase(Path $path)
    {
        $this->values[] = "`{" . $path . "}` IS NOT NULL";
        return $this;
    }
    /**
     * Add LIKE case with percents: `field` LIKE '%value%'
     * 
     * @param string $value
     * @param Path   $path
     * @return \MiniLab\SelMap\Query\Where\OrAnd
     */
    public function addPercentLikeCase($value, Path $path)
    {
        $value = $this->validateValue($value, $path);
        $this->values[] = "`{" . $path . "}` LIKE '%" . $value . "%'";
        return $this;
    }
    /**
     * Comapare with date
     * 
     * @param string    $operator '=', '>=', '>' ...
     * @param \DateTime $value
     * @param Path      $path
     * @return \MiniLab\SelMap\Query\Where\OrAnd
     */
    protected function addDateComparisonCase($operator, \DateTime $value, Path $path)
    {
        $value = $this->validateValue($value, $path);
        $this->values[] = "DATE(`{" . $path . "}`) " . $operator . " STR_TO_DATE('" . $value . "','%Y-%m-%d')";
        return $this;
    }
    /**
     * Comapare with date: comparison two fields
     *
     * @param string $operator '=', '>=', '>' ...
     * @param Path   $path
     * @param Path   $contrPath
     * @return \MiniLab\SelMap\Query\Where\OrAnd
     */
    protected function addDateContrComparisonCase($operator, Path $path, Path $contrPath)
    {
        $this->values[] = "DATE(`{" . $path . "}`) " . $operator . " DATE(`{" . $contrPath . "}`)";
        return $this;
    }
    /**
     * Add new case
     * 
     * @param string $operator '=', '>=', '>' ...
     * @param mixed  $value
     * @param Path   $path
     * @return \MiniLab\SelMap\Query\Where\OrAnd
     */
    protected function addComparisonCase($operator, $value, Path $path)
    {
        $value = $this->validateValue($value, $path);
        $this->values[] = "`{" . $path . "}` " . $operator . " '" . $value . "'";
        return $this;
    }
    /**
     * Add new case: comparison two fields
     *
     * @param string $operator '=', '>=', '>' ...
     * @param Path   $path
     * @param Path   $contrPath
     * @return \MiniLab\SelMap\Query\Where\OrAnd
     */
    protected function addContrComparisonCase($operator, Path $path, Path $contrPath)
    {
        $this->values[] = "`{" . $path . "}` " . $operator . " `{" . $contrPath . "}`";
        return $this;
    }
    /**
     * Place constants and validate field type. Returns escaped string
     * 
     * @param mixed $value
     * @param Path  $path
     * @throws \Exception If table field not found
     * @return string
     */
    protected function validateValue($value, Path $path)
    {
        if(is_string($value)) {
            $f2 = function ($m) {
                if(defined($m[1])) {
                    return constant($m[1]);
                }
                return $m[0];
            };
            $value = preg_replace_callback("/^__(\w+?)__$/", $f2, $value);
        }
        
        $fieldName = substr($path->last(), 1);
        $field = $this->where->map->find($path);
        $tableFields = $field->node->table->fields;
        if(!isset($tableFields[$fieldName])) {
            throw new \Exception("Field not found");
        }
        $tableField = $tableFields[$fieldName];
        $type = DataBase::CELL_TYPES_NAMESPACE . $tableField->type;
        $value = $type::validateInput($value, $tableField, $this->db);
        return $this->db->getConn()->real_escape_string($value);
    }
}