<?php

namespace MiniLab\SelMap\Data;

use MiniLab\SelMap\Data\DataInterface;

/**
 * VirtualCell uses with VirtualDataStruct
 * 
 * @author Oleg Koltunov
 *
 */
class VirtualCell implements DataInterface, CellInterface
{
    /**
     * @var mixed
     */
    protected $value;
    /**
     * 
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
    /**
     * @ignore
     */
    public function __set($name, $value)
    {
        if ($name == "value") {
            $this->value = $value;
        }
        else{
            throw new \Exception("Can not set property " . $name);
        }
    }
    /**
     * @ignore
     */
    public function __get($name)
    {
        if ($name == "value") {
            return $this->$name;
        }
    }
    public function isEmpty()
    {
        return false;
    }
}