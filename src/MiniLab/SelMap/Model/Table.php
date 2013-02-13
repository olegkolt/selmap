<?php 

namespace MiniLab\SelMap\Model;

use MiniLab\SelMap\DataBase;

/**
 *
 * @author Oleg Koltunov <olegkolt@mail.ru>
 * @property-read array    $fields    Table fields
 * @property-read Field    $pKeyField Primary key
 * @property-read Table    $name      Table name
 * @property-read DataBase $db        DataBase object
 *
 */
class Table implements \JsonSerializable
{
    protected $fields;
    protected $name;
    protected $pKeyField;
    protected $db;
    public function __construct(DataBase $db, $name, array $fields, $pKeyFieldName)
    {
        $this->db = $db;
        $this->name = $name;
        $this->fields = $fields;
        $this->pKeyField = $this->fields[$pKeyFieldName];
    }
    /**
     * @ignore
     */
    public function __get($name)
    {
        $props = array("pKeyField", "name", "fields", "db");
        if (in_array($name, $props)) {
            return $this->$name;
        }
        throw new \Exception("Param '" . $name . "' does not exist");
    }
    /**
     * Delete row from table
     * 
     * @param string $pKey Record id
     */
    public function delete($pKey)
    {
        $query = "DELETE FROM `" . $this->name . "` WHERE `" . $this->pKeyField . "` = '" . $pKey . "'";
        $this->db->execNonResult($query);
    }
    public function jsonSerialize()
    {
        return $this->fields;
    }
}