<?php 

namespace MiniLab\SelMap\Data\Struct;

use MiniLab\SelMap\Path\Path;

interface DataStructInterface {
    /**
     * 
     * @param Path $path
     * @return MiniLab\SelMap\Data\DataInterface
     */
    public function &find(Path $path);
    public function setFieldValue($value, Path $path);
    public function setOneToManyRelation($value, Path $path);
}