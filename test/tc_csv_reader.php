<?php
class TcCsvReader extends TcBase {

	function test(){
		$reader = CsvReader::FromData("k1;k2\nv1;v2\nv3;v4");

		$this->assertEquals(2,$reader->getColumnCount());
		$this->assertEquals(3,$reader->getRowCount());

		// getHeader()
		$this->assertEquals(array("k1","k2"),$reader->getHeader());
		$this->assertEquals(array("k1","k2"),$reader->getHeader(0));
		$this->assertEquals(array("v1","v2"),$reader->getHeader(1));

		// getRows()
		$rows = $reader->getRows();
		$this->assertEquals(array(
			array("k1", "k2"),
			array("v1", "v2"),
			array("v3", "v4"),
		),$rows);

		$rows = $reader->getRows(array("offset" => 0));
		$this->assertEquals(array(
			array("k1", "k2"),
			array("v1", "v2"),
			array("v3", "v4"),
		),$rows);

		$rows = $reader->getRows(array("offset" => 2));
		$this->assertEquals(array(
			array("v3", "v4"),
		),$rows);

		// getAssociativeRows()
		$rows = $reader->getAssociativeRows();
		$this->assertEquals(array(
			array("k1" => "v1", "k2" => "v2"),
			array("k1" => "v3", "k2" => "v4")
		),$rows);

		$rows = $reader->getAssociativeRows(array("offset" => 0));
		$this->assertEquals(array(
			array("k1" => "k1", "k2" => "k2"),
			array("k1" => "v1", "k2" => "v2"),
			array("k1" => "v3", "k2" => "v4")
		),$rows);

		$rows = $reader->getAssociativeRows(array("offset" => 2));
		$this->assertEquals(array(
			array("k1" => "v3", "k2" => "v4")
		),$rows);

		$rows = $reader->getAssociativeRows(array("header_line" => 1));
		$this->assertEquals(array(
			array("v1" => "v3", "v2" => "v4")
		),$rows);

		$rows = $reader->getAssociativeRows(array("keys" => array("f1","f2")));
		$this->assertEquals(array(
			array("f1" => "k1", "f2" => "k2"),
			array("f1" => "v1", "f2" => "v2"),
			array("f1" => "v3", "f2" => "v4")
		),$rows);

		$rows = $reader->getAssociativeRows(array("keys" => array("f1","f2"), "offset" => 1));
		$this->assertEquals(array(
			array("f1" => "v1", "f2" => "v2"),
			array("f1" => "v3", "f2" => "v4")
		),$rows);

		$rows = $reader->getAssociativeRows(array("keys" => array("f1","f2","f3")));
		$this->assertEquals(array(
			array("f1" => "k1", "f2" => "k2", "f3" => ""),
			array("f1" => "v1", "f2" => "v2", "f3" => ""),
			array("f1" => "v3", "f2" => "v4", "f3" => "")
		),$rows);

		$rows = $reader->getAssociativeRows(array("keys" => array("f1")));
		$this->assertEquals(array(
			array("f1" => "k1"),
			array("f1" => "v1"),
			array("f1" => "v3")
		),$rows);

		// getRow()
		$this->assertEquals(array("k1","k2"),$reader->getRow(0));
		$this->assertEquals(array("v1","v2"),$reader->getRow(1));
		$this->assertEquals(array("v3","v4"),$reader->getRow(2));
		$this->assertEquals(null,$reader->getRow(3));

		// getColumn()
		$this->assertEquals(array("k1","v1","v3"),$reader->getColumn(0));
		$this->assertEquals(array("k2","v2","v4"),$reader->getColumn(1));
		$this->assertEquals(null,$reader->getColumn(2));
	}

	function test_getColumnCount(){
		$reader = CsvReader::FromData("a;b\nc;d\ne;f");
		$this->assertEquals(2,$reader->getColumnCount());

		$reader = CsvReader::FromData("a;b;x\nc;d\ne;f");
		$this->assertEquals(3,$reader->getColumnCount());

		$reader = CsvReader::FromData("a;b\nc;d;x;x\ne;f");
		$this->assertEquals(4,$reader->getColumnCount());

		$reader = CsvReader::FromData(" ");
		$this->assertEquals(1,$reader->getColumnCount());

		$reader = CsvReader::FromData("");
		$this->assertEquals(0,$reader->getColumnCount());
	}

	function test_DetermineDelimitier(){
		$this->assertEquals(';',CsvReader::DetermineDelimitier("a;b\nc;d"));
		$this->assertEquals(',',CsvReader::DetermineDelimitier("a,b\nc,d"));
		$this->assertEquals('|',CsvReader::DetermineDelimitier("a|b\nc|d"));
		$this->assertEquals("\t",CsvReader::DetermineDelimitier("a\tb\nc\td"));

		$this->assertEquals(';',CsvReader::DetermineDelimitier("a;b\nc\nd;e"));
		$this->assertEquals(',',CsvReader::DetermineDelimitier("a,b\nc\nd;e"));

		$this->assertEquals("\t",CsvReader::DetermineDelimitier("01\tapple, red"));

		$this->assertEquals(',',CsvReader::DetermineDelimitier("no delimitier"));
		$this->assertEquals(',',CsvReader::DetermineDelimitier(""));
	}

	function test_exchange_rate(){
		$reader = CsvReader::FromFile(__DIR__ . "/files/denni_kurz.txt");

		$data = $reader->getAssociativeRows(array("header_line" => 1));
		$this->assertEquals(33,sizeof($data));
		$this->assertEquals(array(
			'země' => 'Austrálie',
			'měna' => 'dolar',
			'množství' => '1',
			'kód' => 'AUD',
			'kurz' => '15,872'
		),$data[0]);
		$this->assertEquals(array(
			'země' => 'Velká Británie',
			'měna' => 'libra',
			'množství' => '1',
			'kód' => 'GBP',
			'kurz' => '29,061'
		),$data[32]);

		$data = $reader->getAssociativeRows(array("keys" => array("country","currency","amount","currency_code","rate"),"offset" => 2));
		$this->assertEquals(33,sizeof($data));
		$this->assertEquals(array(
			'country' => 'Austrálie',
			'currency' => 'dolar',
			'amount' => '1',
			'currency_code' => 'AUD',
			'rate' => '15,872'
		),$data[0]);
		$this->assertEquals(array(
			'country' => 'Velká Británie',
			'currency' => 'libra',
			'amount' => '1',
			'currency_code' => 'GBP',
			'rate' => '29,061'
		),$data[32]);
	}

}
