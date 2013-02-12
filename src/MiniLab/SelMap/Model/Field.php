<?php 

namespace MiniLab\SelMap\Model;

/**
 *
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property-read array $rel  Field relations
 * @property-read Field $name Field name
 * 
 */
class Field implements \JsonSerializable
{
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
        $props = array("rel", "name");
        if (in_array($name, $props)) {
            return $this->$name;
        }
    }
    public function jsonSerialize()
    {
        return $this->name;
    }
}