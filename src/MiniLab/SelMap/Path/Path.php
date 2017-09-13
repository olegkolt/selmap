<?php

namespace MiniLab\SelMap\Path;

/**
 *
 * Path class. Implements Iterator
 * @author Oleg Koltunov <olegkolt@mail.ru>
 *
 */
class Path implements \Iterator{
    protected $position = 0;
    /**
     * An array of path elements
     * @var array
     */
    protected $elements = array();
    /**
     *
     * Create a Path object
     * @param mixed $path StringType or array
    */
    public function __construct($path) {
        $this->position = 0;
        if($path == "") {
            return;
        }
        if(is_string($path)) {
            $path = explode("/", $path);
        }
        if(is_array($path)) {
            $this->readPath($path);
            return;
        }
        throw new \Exception("Path has uncorrect format");
    }
    protected function readPath(array $path) {
        $isLastRel = null;
        foreach ($path as $el) {
            $node = new PathNode($el);
            if ($node->getType() == PathNodeType::FIELD) {
                if($isLastRel === true || is_null($isLastRel)) {
                    $isLastRel = false;
                } else {
                    throw new \Exception("Path string is not correct. After field element must be table");
                }
            } elseif ($node->getType() == PathNodeType::RELATION) {
                if($isLastRel === false || is_null($isLastRel)) {
                    $isLastRel = true;
                } else {
                    throw new \Exception("Path string is not correct. After table element must be field");
                }
            }
            $this->elements[] = $node;
        }
    }
    /**
     * Get path string
     * @return string StringType path representation
     */
    public function __toString() {
        return implode("/", $this->elements);
    }
    /**
     * Addition of two Path objects. The $additionPath will be added to the end of the current Path object
     * @param Path $additionPath
     * @return Path Resulting object
     */
    public function add(Path $additionPath) {
        $array = $additionPath->asArray();
        $this->elements = array_merge($this->elements, $array);
        return $this;
    }
    /**
     * Get path object as array
     * @return array result array
     */
    public function asArray() {
        return $this->elements;
    }
    /**
     * Get new path without array element (e.g. '@@current', '[0]')
     * @return Path result object
     */
    public function withoutArrayElements() {
        $newPath = array();
        foreach ($this->elements as $el) {
            if ($el->getType() != PathNodeType::ARRAYTYPE) {
                $newPath[] = $el;
            }
        }
        return new Path($newPath);
    }
    /**
     * Get the last element of the path
     * @return PathNode last path element
     */
    public function last() {
        return end($this->elements);
    }
    /**
     * Get the first element of the path
     * @return PathNode last path element
     */
    public function first() {
        if(isset($this->elements[0])) {
            return $this->elements[0];
        }
        return new PathNode("");
    }
    /**
     * Get new path without last element
     * @return Path result object
     */
    public function withoutLast() {
        $copy = $this->elements;
        array_pop($copy);
        return new Path($copy);
    }
    /**
     * Get new path without first element
     * @return Path result object
     */
    public function withoutFirst() {
        $copy = $this->elements;
        array_shift($copy);
        return new Path($copy);
    }
    public function rewind() {
        $this->position = 0;
    }

    public function current() {
        return $this->elements[$this->position];
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        ++$this->position;
    }

    public function valid() {
        return isset($this->elements[$this->position]);
    }
}