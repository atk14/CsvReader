<?php
/**
 * Reads CSV data from a file or a string. Detects automatically CSV format parameters.
 *
 * See README.md
 **/

class CsvReader {

	protected $options;
	protected $data;

	/**
	 * Import from given file
	 **/
	static function FromFile($filename, $options = array()) {
		$f = fopen($filename, 'r');
		if(!$f) {
			throw new \Exception("Bad file $filename");
		}
		$options = static::DetermineOptions(file_get_contents($filename), $options);

		// skipping BOM
		$bom = fread($f,3);
		if(!in_array($bom,array("\xEF\xBB\xBF"))){
			fseek($f,0);
		}

		return new static($f, $options);
	}

	/**
	 * Import from given string
	 **/
	static function FromData($data, $options = array()) {
		// skipping BOM
		if(in_array(substr($data,0,3),array("\xEF\xBB\xBF"))){
			$data = substr($data,3);
		}

		$stream = fopen('php://temp','r+');
		fwrite($stream, $data);
		rewind($stream);
		$options = static::DetermineOptions($data, $options);
		return new static($stream, $options);
	}

	/**
	 * Parse options and guess default values from data
	 **/
	static function DetermineOptions($data, $options = array()) {
		$options += array(
			'delimiter' => null,
			'quote' => null,
		);


		if($options['delimiter'] === null) {
			$options['delimiter'] = self::DetermineDelimitier($data);
		}

		if($options['quote'] === null) {
			if(preg_match("[\"']", $data, $matches)) {
				$options['quote'] = $matches[0];
			} else {
				$options['quote'] = '"';
			}
		}

		return $options;
	}

	static function DetermineDelimitier($data) {
		$default_delimitier = ",";
		$delimitiers = array("\t",",",";","|"); // tab is more preferable delimiter than comma

		$counters = str_split(str_repeat("0",sizeof($delimitiers)),1); // array("0","0", ...);
		$lines = explode("\n",$data);
		$lines_count = sizeof($lines);

		foreach($lines as $line){
			foreach($delimitiers as $k => $d){
				if(!is_bool(strpos($line,$d))){
					$counters[$k]++;
				}
			}
		}

		$max = 0;
		$delimitier = null;
		foreach($delimitiers as $k => $d){
			if($max<$counters[$k]){
				$max = $counters[$k];
				$delimitier = $d;
			}
		}
		if(!is_null($delimitier)){ return $delimitier; }
		
		return $default_delimitier; // default
	}

	function getHeader($header_line = 0){
		return isset($this->data[$header_line]) ? $this->data[$header_line] : array();
	}

	function getColumnCount(){
		return sizeof($this->getHeader());
	}

	function getRowCount(){
		return sizeof($this->data);
	}

	/**
	 * Use FromData or FromFile instead.
	 *
	 * @param resource $stream
	 * @param array $options
	 * - ...
	 */
	protected function __construct($stream, $options = array()) {
		$options += array(
			'delimiter' => ',',
			'quote' => '"',
			'lower_header_names' => false,
			'trim_header_names' => true,
		);

		$this->options = $options;

		$this->readBody($stream);
	}

	/**
	 *
	 *	$data = $reader->getAssociativeRows();
	 *	$data = $reader->getAssociativeRows(["header_line" => 2]); // 3rd line, countring from 0
	 *	$data = $reader->getAssociativeRows(["keys" => ["name","email","city"]]);
	 *	$data = $reader->getAssociativeRows(["keys" => ["name","email","city"],"offset" => 1]);
	 */
	function getAssociativeRows($options = array()) {
		$options += array(
			"keys" => null, // ["key1","key2","key3", ...]
		);
		$keys = $options["keys"];

		$options += array(
			"header_line" => is_null($keys) ? 0 : null,
		);
		$header_line = $options["header_line"];

		$options += array(
			"offset" => is_null($header_line) ? 0 : ($header_line + 1)
		);
		$offset = $options["offset"];

		$header_line = $options["header_line"];

		$out = array();

		if(is_null($keys)){
			$keys = $this->getHeader($header_line);
		}

		for($i=$offset;$i<sizeof($this->data);$i++){
			$out[] = $this->toAssociativeRow($this->data[$i],$keys);
		}

		return $out;
	}

	function getRows($options = array()) {
		$options += array(
			"offset" => 0,
		);
		if($options["offset"]>0){
			return array_slice($this->data,$options["offset"]);
		}
		return $this->data;
	}

	function getRow($index){
		$index = (int)$index;
		if($index==0){
			return $this->getHeader();
		}
		return isset($this->data[$index]) ? $this->data[$index] : null;
	}

	function getColumn($index,$options = array()){
		$options += array(
			"offset" => 0,
		);

		if($index>$this->getColumnCount()-1){
			return array();
		}

		$out = array();
		foreach($this->data as $row){
			$out[] = isset($row[$index]) ? $row[$index] : "";
		}
		
		$out = array_slice($out,$options["offset"]);
		return $out;
	}

	/**
	 * Read line from csv strem returning array of values
	 */
	protected function readline($stream) {
		return fgetcsv($stream, 0, $this->options['delimiter'], $this->options['quote']);
	}

	/** Read body of csv file**/
	protected function readBody($stream) {
		$column_count = 0;
		$this->data = array();
		while(!feof($stream)) {
			$line = $this->readline($stream);
			if($line) {
				$this->data[] = $line;
				$column_count = max($column_count,sizeof($line));
			}
		}

		foreach($this->data as $k => $line){
			if(sizeof($line)<$column_count){
				$this->data[$k] = array_pad($line, $column_count, '');
			}
		}
	}

	protected function toAssociativeRow($row,$keys = null) {
		if(is_null($keys)){ $keys = $this->header; }
		$hc = count($keys);
		$row = array_slice(array_pad($row, $hc, ''),0,$hc);
		return array_combine($keys, $row);
	}
}
