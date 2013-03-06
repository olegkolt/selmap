<?php

namespace MiniLab\SelMap\Data\Struct;

use MiniLab\SelMap\Data\VirtualCell;
use MiniLab\SelMap\Data\EmptyCell;
use MiniLab\SelMap\Path\Path;

/**
 *
 * The DataStruct not connected with DB
 * @author Oleg Koltunov <olegkolt@mail.ru>
 *
 */
class VirtualDataStruct extends DataStructBase
{
    /**
     * 
     * @var array(VirtualCell)
     */
    protected $data = array();
    public function __construct($fields = array())
    {
        foreach ($fields as $f) {
            $this->data[$f] = new EmptyCell();
        }
    }
    public function __set($name, $value)
    {
        if($name == "data" && is_array($value)) {
            foreach ($value as $k => $v) {
                $this->data[$k] = new VirtualCell($v);
            }
            return;
        }
        throw new \InvalidArgumentException("Property data must be array");
    }
    public function &find(Path $path)
    {
        $path = $this->transformPath($path);
        if (isset($this->data[$path])) {
            return $this->data[$path];
        }
        $f = new EmptyCell();
        return $f;
    }
    public function createRecords()
    {

    }
    public function setFieldValue($value, Path $path)
    {
        $this->data[$this->transformPath($path) ] = new VirtualCell($value);
    }
    protected function transformPath($path)
    {
        return substr((string)$path->first(), 1);
    }
    public function setOneToManyRelation($value, Path $path)
    {
        $this->setFieldValue($value, $path);
    }
}