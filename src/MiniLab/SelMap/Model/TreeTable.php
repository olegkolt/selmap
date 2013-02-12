<?php 

namespace MiniLab\SelMap\Model;

use MiniLab\SelMap\DataBase;

/**
 *
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property-read Field $parentField
 *
 */
class TreeTable extends Table {
    protected $parentField;
    public function __construct(DataBase $db, $name, array $fields, $pKeyFieldName, $parentFieldName) {
        parent::__construct($db, $name, $fields, $pKeyFieldName);
        $this->parentField = $this->fields[$parentFieldName];
    }
    /**
     * @ignore
     */
    public function __get($name) {
        if ($name == "parentField") {
            return $this->$name;
        }
        return parent::__get($name);
    }
}