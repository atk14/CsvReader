<?php
class TcCsvReader extends TcBase {

	function test(){
		$reader = CsvReader\CsvReader::FromData("k1;k2\nv1;v2\nv3;v4");

		$rows = $reader->asArray();
		$this->assertEquals(array(
			array("k1", "k2"),
			array("v1", "v2"),
			array("v3", "v4"),
		),$rows);

		$rows = $reader->asAssociative();
		$this->assertEquals(array(
			array("k1" => "v1", "k2" => "v2"),
			array("k1" => "v3", "k2" => "v4")
		),$rows);

		$rows = $reader->asAssociative(array("f1","f2"));
		$this->assertEquals(array(
			array("f1" => "k1", "f2" => "k2"),
			array("f1" => "v1", "f2" => "v2"),
			array("f1" => "v3", "f2" => "v4")
		),$rows);

		$rows = $reader->asAssociative(array("f1","f2","f3"));
		$this->assertEquals(array(
			array("f1" => "k1", "f2" => "k2", "f3" => ""),
			array("f1" => "v1", "f2" => "v2", "f3" => ""),
			array("f1" => "v3", "f2" => "v4", "f3" => "")
		),$rows);

		$rows = $reader->asAssociative(array("f1"));
		$this->assertEquals(array(
			array("f1" => "k1"),
			array("f1" => "v1"),
			array("f1" => "v3")
		),$rows);
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
