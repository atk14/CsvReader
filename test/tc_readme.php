<?php
class TcReadme extends TcBase {

	function test(){
    $csv_data = '
k1;k2;k3
v1;v2;v3
x1;x2;x3
y1;y2
z1
    ';
    $csv_data = trim($csv_data);

    $reader = CsvReader::FromData($csv_data);

		$this->assertEquals(3,$reader->getTotalColumnCount());
		$this->assertEquals(5,$reader->getTotalRowCount());

    $rows = $reader->getRows();
		$this->assertEquals(array(
			array("k1","k2","k3"),
			array("v1","v2","v3"),
			array("x1","x2","x3"),
			array("y1","y2",""),
			array("z1","",""),
		),$rows);

    $rows = $reader->getRows(array("offset" => 2));
		$this->assertEquals(array(
			array("x1","x2","x3"),
			array("y1","y2",""),
			array("z1","",""),
		),$rows);

    $row = $reader->getRow(0);
		$this->assertEquals(
			array("k1","k2","k3")
		,$row);

    $row = $reader->getRow(1);
		$this->assertEquals(
			array("v1","v2","v3")
		,$row);

    $col = $reader->getColumn(0);
		$this->assertEquals(
			array("k1","v1","x1","y1","z1")
		,$col);

    $col = $reader->getColumn(0,array("offset" => 1));
		$this->assertEquals(
			array("v1","x1","y1","z1")
		,$col);

    $col = $reader->getColumn(2);
		$this->assertEquals(
			array("k3","v3","x3","","")
		,$col);

    $col = $reader->getColumn(3);
		$this->assertEquals(null,$col);

		$rows = $reader->getAssociativeRows();
		$this->assertEquals(array(
			array("k1" => "v1", "k2" => "v2", "k3" => "v3"),
			array("k1" => "x1", "k2" => "x2", "k3" => "x3"),
			array("k1" => "y1", "k2" => "y2", "k3" => ""),
			array("k1" => "z1", "k2" => "", "k3" => ""),
		),$rows);

		$rows = $reader->getAssociativeRows(array("keys" => array("f1","f2","f3")));
		$this->assertEquals(array(
			array("f1" => "k1", "f2" => "k2", "f3" => "k3"),
			array("f1" => "v1", "f2" => "v2", "f3" => "v3"),
			array("f1" => "x1", "f2" => "x2", "f3" => "x3"),
			array("f1" => "y1", "f2" => "y2", "f3" => ""),
			array("f1" => "z1", "f2" => "", "f3" => ""),
		),$rows);

		$rows = $reader->getAssociativeRows(array("keys" => array("f1","f2","f3"),"offset" => 2));
		$this->assertEquals(array(
			array("f1" => "x1", "f2" => "x2", "f3" => "x3"),
			array("f1" => "y1", "f2" => "y2", "f3" => ""),
			array("f1" => "z1", "f2" => "", "f3" => ""),
		),$rows);
	}
}
