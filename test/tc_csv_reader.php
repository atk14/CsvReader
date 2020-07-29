<?php
class TcCsvReader extends TcBase {

	function test(){
		$reader = CsvReader\CsvReader::FromData("k1;k2\nv1;v2\nv3;v4");
		$rows = array();
		foreach($reader->associative() as $row){
			$rows[] = $row;
		}
		$this->assertEquals(2,sizeof($rows));
		$this->assertEquals(array("k1" => "v1", "k2" => "v2"),$rows[0]);
		$this->assertEquals(array("k1" => "v3", "k2" => "v4"),$rows[1]);
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
