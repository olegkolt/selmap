<?php

use MiniLab\SelMap\DataBase;
use MiniLab\SelMap\Reader\XmlReader;

class SelMapConnectedTest extends \PHPUnit_Framework_TestCase
{
    protected $db;
    public function setUp()
    {
        $this->db = new DataBase();
        $reader = new XmlReader($this->db);
        $reader->readSchema(XmlReader::readXmlFile($GLOBALS['DB_SCHEMA_PATH']));
        $this->db->connect($GLOBALS['DB_HOST'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASS'], $GLOBALS['DB_NAME']);
    }
    public function tearDown()
    {
        $this->db->close();
    }
}