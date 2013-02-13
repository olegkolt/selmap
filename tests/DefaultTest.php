<?php

use MiniLab\SelMap\DataBase;
use MiniLab\SelMap\Path\Path;
use MiniLab\SelMap\Data\Struct\DataStruct;
use MiniLab\SelMap\Reader\XmlReader;
use MiniLab\SelMap\Query\QueryMap;
use MiniLab\SelMap\Query\Where\Where;

class DefaultTest extends \PHPUnit_Framework_TestCase 
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
    public function testSelection()
    {
        $map = $this->db->createMap()->addTable("order")
            ->addField("comment")->node
            ->addField("id")
                ->addTable("order_product", "order")
                    ->addField("count")->node
                    ->addField("product")
                        ->addTable("product", "id")
                            ->addField("name")->node
                            ->addField("number")->node
                        ->parent
                    ->node->parent
                ->node
            ->addField("date")
            ->map;
        
        $where = $map->createWhere()->addOrAnd()
                ->addEqualCase(1, new Path("@id"))
            ->where;
        
        $this->doSelection($map, $where);
    }
    public function testSelectionXmlReader()
    {
        $reader = new XmlReader($this->db);
        $map = $reader->readMap(XmlReader::readXmlFile(__DIR__ . "/simpleMap.xml"));
        $where = $reader->readWhere($map, XmlReader::readXmlFile(__DIR__ . "/simpleWhere.xml"));
        $this->doSelection($map, $where);
    }
    protected function doSelection(QueryMap $map, Where $where)
    {
        $ds = new DataStruct($map);
        $ds->select($where);
        
        $result = array();
        $result["comment"] = $ds->find(new Path("@comment"))->value;
        $result["date"]    = $ds->find(new Path("@date"))->value;
        $result["rows"]    = array();
        foreach($ds->find(new Path("@id/order_product:order")) as $r) {
            $row = array();
            $row["id"]     = DataStruct::search($r, new Path("@id"))->value;
            $row["number"] = DataStruct::search($r, new Path("@product/product:id/@number"))->value;
            $row["name"]   = DataStruct::search($r, new Path("@product/product:id/@name"))->value;
            $row["count"]  = DataStruct::search($r, new Path("@count"))->value;
            $result["rows"][] = $row;
        }
        
        $expected = array ( 'comment' => 'Первый заказ',
                'date' => new \DateTime('2013-02-08'),
                'rows' => array (
                        0 => array ( 'id' => 3, 'number' => '12', 'name' => 'Советский флаг', 'count' => 2, ),
                        1 => array ( 'id' => 4, 'number' => '147', 'name' => 'Бюст Ленина', 'count' => 4, ),
                ),
        );
        $this->assertEquals($expected, $result);
        
        $count = $ds->find(new Path("@id/order_product:order/@@current/@count"))->value;
        
        $this->assertInternalType("int", $count, "Count type must be int");
    }
    protected function selectFirstOrder()
    {
        $map = $this->db->createMap()->addTable("order")->addAllFields()->map;
        $ds = new DataStruct($map);
        $ds->selectById(1);
        return $ds;
    }
    public function testDataTypes()
    {
        $ds = $this->selectFirstOrder();
        $status = $ds->find(new Path("@status"))->value;
        $this->assertInternalType("int", $status, "Status type must be int");
        $date = $ds->find(new Path("@date"))->value;
        $this->assertInstanceOf("\\DateTime", $date, "Date must be instance of DateTime");
    }
    /**
     * @expectedException InvalidArgumentException
     */
    public function testDataTypeExceptions()
    {
        $ds = $this->selectFirstOrder();
        
        $date = $ds->find(new Path("@date"));
        $date->value = "2013-02-08";
    }
    public function testPaged()
    {
        $itemsCount = 10;
        $map = $this->db->createMap()->addTable("product")->addAllFields()->map;
        $ds = new DataStruct($map);
        $ds->itemsPerPage = $itemsCount;
        $ds->select(null, 1);
        $this->assertEquals($itemsCount, count($ds->row));
    }
    public function testPagedBranched()
    {
        $itemsCount = 5;
        $reader = new XmlReader($this->db);
        $map = $reader->readMap(XmlReader::readXmlFile(__DIR__ . "/simpleMap.xml"));
        
        $ds = new DataStruct($map);
        $ds->itemsPerPage = $itemsCount;
        $ds->select(null, 1);
        $this->assertEquals($itemsCount, count($ds->row));
    }
}