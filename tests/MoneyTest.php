<?php

use MiniLab\SelMap\DataBase;
use MiniLab\SelMap\Data\Struct\DataStruct;
use MiniLab\SelMap\Path\Path;
use Money\Money;

include_once __DIR__ . '/SelMapConnectedTest.php';

class MoneyTest extends SelMapConnectedTest
{
    public function testMoneySelection()
    {
        $map = $this->db->createMap()->addTable("order_product")->addAllFields()->map;
        $ds = new DataStruct($map);
        $ds->selectById(3);
        $price = $ds->find(new Path("@price"))->value;
        $this->assertInstanceOf('Money\Money', $price);
    }
    public function testMoneyCreationAndDeletion()
    {
        $price = Money::RUB(1500);
        $map = $this->db->createMap()->addTable("order_product")->addAllFields()->map;
        $ds = new DataStruct($map);
        $ds->createRecords();
        $ds->setFieldValue(1, new Path("@order"));
        $ds->setFieldValue(3, new Path("@product"));
        $ds->setFieldValue(3, new Path("@count"));
        $ds->setFieldValue($price, new Path("@price"));
        $ds->save();
        $id = $ds->find(new Path("@id"))->value;
        
        $this->assertLessThan($id, 1);
        
        $this->db->getTable("order_product")->delete($id);
    }
}