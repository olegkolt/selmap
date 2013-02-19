<?php

namespace MiniLab\SelMap\Data;

class RecordSet implements \ArrayAccess, \Iterator, \JsonSerializable, \Countable, DataInterface 
{
    protected $container = array();
    public function __construct($array = null)
    {
        if(is_null($array)) {
            return;
        }
        if(!is_array($array)) {
            throw new \InvalidArgumentException("Argument must be array");
        }
        $this->container = $array;
    }
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }
    public function rewind()
    {
        reset($this->container);
    }
    public function current()
    {
        return current($this->container);
    }
    public function key() 
    {
        $var = key($this->container);
        return $var;
    }
    public function next() 
    {
        $var = next($this->container);
        return $var;
    }
    public function valid()
    {
        $key = key($this->container);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }
    public function jsonSerialize()
    {
        return $this->container;
    }
    public function isEmpty()
    {
        return count($this->container) == 0;
    }
    public function count()
    {
        return count($this->container);
    }
    public function first()
    {
        $this->rewind();
        return $this->current();
    }
}