<?php 

namespace MiniLab\SelMap\Data\Struct;

use MiniLab\SelMap\Data\Struct\DataStructInterface;

abstract class DataStructBase implements DataStructInterface 
{
    protected $onSave = array();
    /**
     * Set save handler function
     * @param function $func
    */
    public function setOnSave($func) {
        $this->onSave[] = $func;
    }

    /**
     * Save changes
     *
     */
    public function save() {
        while (list($k, $f) = each($this->onSave)) {
            $f($this);
        }
        $this->onSave = array();
    }
    /**
     * Create one root record and related records if it is necessary
     * @return mixed
     */
    public abstract function createRecords();
}