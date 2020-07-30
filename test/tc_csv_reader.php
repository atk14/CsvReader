<?php
class TcCsvReader extends TcBase {

	function test(){
		$reader = CsvReader\CsvReader::FromData("k1;k2\nv1;v2\nv3;v4");

		$rows = array();
		foreach($reader->asAssociative() as $row){
			$rows[] = $row;
		}
		$this->assertEquals(2,sizeof($rows));
		$this->assertEquals(array("k1" => "v1", "k2" => "v2"),$rows[0]);
		$this->assertEquals(array("k1" => "v3", "k2" => "v4"),$rows[1]);

		$rows = array();
		foreach($reader->asAssociative(array("f1","f2")) as $row){
			$rows[] = $row;
		}
		$this->assertEquals(3,sizeof($rows));
		$this->assertEquals(array("f1" => "k1", "f2" => "k2"),$rows[0]);
		$this->assertEquals(array("f1" => "v1", "f2" => "v2"),$rows[1]);
		$this->assertEquals(array("f1" => "v3", "f2" => "v4"),$rows[2]);

		$rows = array();
		foreach($reader->asArray() as $row){
			$rows[] = $row;
		}
		$this->assertEquals(3,sizeof($rows));
		$this->assertEquals(array("k1", "k2"),$rows[0]);
		$this->assertEquals(array("v1", "v2"),$rows[1]);
		$this->assertEquals(array("v3", "v4"),$rows[2]);
	}

	function test_DetermineDelimitier(){
		$this->assertEquals(';',CsvReader\CsvReader::DetermineDelimitier("a;b\nc;d"));
		$this->assertEquals(',',CsvReader\CsvReader::DetermineDelimitier("a,b\nc,d"));
		$this->assertEquals('|',CsvReader\CsvReader::DetermineDelimitier("a|b\nc|d"));
		$this->assertEquals("\t",CsvReader\CsvReader::DetermineDelimitier("a\tb\nc\td"));

		$this->assertEquals(';',CsvReader\CsvReader::DetermineDelimitier("a;b\nc\nd;e"));
		$this->assertEquals(',',CsvReader\CsvReader::DetermineDelimitier("a,b\nc\nd;e"));

		$this->assertEquals(',',CsvReader\CsvReader::DetermineDelimitier("no delimitier"));
		$this->assertEquals(',',CsvReader\CsvReader::DetermineDelimitier(""));
	}
}
