<?php 

namespace MiniLab\SelMap\Data\Struct;

use MiniLab\SelMap\Path\Path;

interface DataStructInterface {
    public function &find(Path $path);
    public function setFieldValue($value, Path $path);
    public function setOneToManyRelation($value, Path $path);
}