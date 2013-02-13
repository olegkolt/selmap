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
    public function __construct($name)
    {
        $this->name = (string)$name;
    }
    public function __toString()
    {
        return (string)$this->name;
    }
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
     * 
     * @param string $type Type name from namespace MiniLab\SelMap\Data\CellTypes (String, Int, etc.)
     */
    public function setType($type)
    {
        $this->type = $type;
    }
    public function jsonSerialize()
    {
        return $this->type . ": " . $this->name;
    }
}