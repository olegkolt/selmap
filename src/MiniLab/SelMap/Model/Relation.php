<?php 

namespace MiniLab\SelMap\Model;


/**
 *
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property bool $inherite True if this is inherit relation (PK to PK). Default false
 * @property-read string $key
 * @property-read string $fKey
 * @property-read Table $table
 * @property-read Relation $crossRel
 *
 */
class Relation {
    protected $table;
    protected $key;
    protected $fTable;
    protected $fKey;
    protected $inherite = false;
    protected $crossRel;
    public function __construct(Table $table, $key, Table $fTable, $fKey) {
        $this->table = $table;
        $this->key = $key;
        $this->fTable = $fTable;
        $this->fKey = $fKey;
    }
    /**
     * Get relation type: one-to-one (false), one-to-many (true) or many-to-one (false)
     * @return bool
     */
    public function isFTableArray() {
        if ($this->inherite) {
            return false;
        }
        return $this->fTable->pKeyField != $this->fKey;
    }
    /**
     *
     * Set cross relation
     * @param Relation $rel
     */
    public function setCrossRelation(Relation $rel) {
        $this->crossRel = $rel;
    }
    /**
     * @ignore
     */
    public function __set($name, $value) {
        if ($name == "inherite") {
            $this->inherite = (bool)$value;
        }
    }
    /**
     * @ignore
     */
    public function __get($name) {
        $props = array("inherite", "table", "fTable", "key", "fKey", "crossRel");
        if (in_array($name, $props)) {
            return $this->$name;
        }
    }
}