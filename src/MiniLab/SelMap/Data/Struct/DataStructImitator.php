<?php

namespace MiniLab\SelMap\Data\Struct;

use MiniLab\SelMap\Path\Path;
use MiniLab\SelMap\Data\Struct\DataStructInterface;
use MiniLab\SelMap\Data\Struct\DataStruct;

class DataStructImitator implements DataStructInterface {
    protected $ds;
    protected $pathPrefix;
    /**
     *
     * If $ds is 'null' $pathPrefix will set to 'false'
     * @param mixed $ds DataStruct or null
     */
    public function __construct(DataStruct $ds = null){
        if($ds === null) {
            $this->pathPrefix = false;
            return;
        }
        $this->ds = $ds;
    }
    /**
     * Set path prefix
     * @param Path $path path prefix
     */
    public function setPathPrefix(Path $path) {
        $this->pathPrefix = $path;
    }
    /**
     * Set path prefix to false - search will always return false
     */
    public function setPathPrefixToFalse() {
        $this->pathPrefix = false;
    }
    public function &find(Path $path) {
        if($this->pathPrefix === false) {
            $f = false;
            return $f;
        }
        $path = $this->handlePathPrefix($path);
        return $this->ds->find($path);
    }
    public function setFieldValue($value, Path $path) {
        //echo "=== " . $path . "<br />";
        if($this->pathPrefix === false) {
            return false;
        }
        $path = $this->handlePathPrefix($path);
        return $this->ds->setFieldValue($value, $path);
    }
    public function setOneToManyRelation($value, Path $path) {
        if($this->pathPrefix === false) {
            return false;
        }
        $path = $this->handlePathPrefix($path);
        return $this->ds->setOneToManyRelation($value, $path);
    }
    protected function handlePathPrefix(Path $path) {
        if (!is_null($this->pathPrefix)) {
            $copy = clone $this->pathPrefix;
            $path = $copy->add($path);
        }
        return $path;
    }
}