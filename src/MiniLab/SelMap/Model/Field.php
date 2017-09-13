<?php 

namespace MiniLab\SelMap\Model;

/**
 *
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property-read array  $rel  Field relations
 * @property-read string $name Field name
 * @property-read string $type Type name. "Cell" is default
 */
class Field implements \JsonSerializable
{
    protected $type = "Cell";
    protected $name;
    protected $rel = array();
    protected $nullable;
    /**
     * 
     * @param string $name Field name
     */
    public function __construct($name, $nullable = false)
    {
        $this->name = (string)$name;
        $this->nullable = (bool)$nullable;
    }
    /**
     * Get field name
     * 
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }
    /**
     * 
     * @param string $relName
     * @param Relation $rel
     * @return void
     */
    public function setRel($relName, Relation $rel)
    {
        $this->rel[$relName] = $rel;
    }
    /**
     * @ignore
     */
    public function __get($name)
    {
        $props = array("rel", "name", "type");
        if (in_array($name, $props)) {
            return $this->$name;
        }
    }
    /**
     * Set field type
     * 
     * @param string $type Type name from namespace MiniLab\SelMap\Data\CellTypes (StringType, Int, etc.)
     */
    public function setType($type)
    {
        $this->type = $type;
    }
    /**
     * Whether the field can be null
     * 
     * @return boolean
     */
    public function isNullable()
    {
        return $this->nullable;
    }
    public function jsonSerialize()
    {
        $n = " NOT NULL";
        if($this->nullable) {
            $n = " NULL";
        }
        return $this->type . ": " . $this->name . $n;
    }
}