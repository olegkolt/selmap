<?php

namespace MiniLab\SelMap\Path;

class PathNode {
    protected $type;
    protected $str;

    public function __construct($str){
        if($this->type = self::defineType($str)) {
            $this->str = (string)$str;
            return;
        }
        throw new \Exception("Unknown path node type. Node: '" . $str . "'");
    }
    /**
     * Get type of the node
     * @return PathNodeType
     */
    public function getType() {
        return $this->type;
    }

    public function __toString() {
        return $this->str;
    }

    public static function defineType($node) {
        if($node instanceof PathNode) {
            return $node->getType();
        }
        $str = (string)$node;
        if($str == "") {
            return PathNodeType::EMPTYTYPE;
        }elseif ($str == "@@current" || ($str[0] == "[" && substr($str, -1) == "]")) {
            return PathNodeType::ARRAYTYPE;
        }elseif ($str[0] == "@") {
            return PathNodeType::FIELD;
        }elseif (strpos($str, ":") != false) {
            return PathNodeType::RELATION;
        }
        return false;
    }
}