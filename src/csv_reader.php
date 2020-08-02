<?php
namespace CsvReader;

/**
 * Class for importing CSV.
 *
 * Usage:
 *
 * ```
 * $import = CsvImport::FromFile($filename, array(
 * 	'unique' => array('id', 'catalog_id'),
 * 	'allowed' => array('id', 'catalog_id', 'name', description', 'created_at'),
 * 	'required' => array('catalog_id'),
 * 	'format' => array('id' => integer, 'created_at' => 'date')
 * )
 * );
 * ```
 *
 * Process each row
 * ```
 * foreach($import as $row) {
 * 	echo $row[0], $row[1], ...
 * }
 * ```
 *
 * ```
 * foreach($import->associative() as $row) {
 * 	echo $row['id'], $row['catalog_id'], ...
 * }
 *
 * foreach($import->formated('db') as $row) {
 * 	Record::CreateNewRecord($row->associative())
 * }
 *
 * foreach($import->formated('view') as $row) {
 * 	//vytiskni radku tabulky
 * }
 * ```
 *
 * Errors checking
 * ```
 * $import->hasError(); //are there errors during import?
 * $import->hasError(1); /are there errors on second line of data (first has row_number 0)
 * $import->hasError(CsvImport::HEADER); //are there errors in CSV header (unallowed field, etc...?)
 * $import->hasError(1,'id'); //are there errors on second line on field 'id'?
 * $import->hasError(1,2); //are there errors on second line in second field
 * ```
 *
 * $import->getError();	//returns array of lines, where are errors. Each line is represented by
 * array, where nonnegative keys are index of errors related to given fields (by its index),
 * negative keys denotes errors related to whole row.
 *
 * Never create the class by constructor, use CsvImport::FromFile and CsvImport::FromData static methods.
 **/

class CsvReader extends \ArrayIterator {

	static $Error;

	static $FormatRoutines;

	/**
	 * ID of header row (e.g. for addError)
	 **/
	const HEADER = -1;
	const ROW = -2;

	/**
	 * Import from given file
	 **/
	static function FromFile($filename, $options = array()) {
		$f = fopen($filename, 'r');
		if(!$f) {
			throw new Exception("Bad file $filename");
		}
		$options = static::DetermineOptions(file_get_contents($filename), $options);
		return new static($f, $options);
	}

	/**
	 * Import from given string
	 **/
	static function FromData($data, $options = array()) {
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
			'allowed_regexp' => array(),
			'quote' => null,
			'unique' => array(),
			'not_null' => array(),	/* less strong than required -
			it's not error when the field is not present, only when there is and is empty */
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

		if(!$options['allowed_regexp']) {
			$options['allowed_regexp'] = array();
		}

		return $options;
	}

	static function DetermineDelimitier($data) {
		$delimitiers = array(",",";","\t","|");

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
		
		return $delimitiers[0]; // default
	}

	function getHeader(){
		return $this->header;
	}

	function getColumnCount(){
		return sizeof($this->getHeader());
	}

	function getTotalRowCount(){
		return sizeof($this->data) + 1;
	}

	/**
	 * Returns given row or cell if ($col !== null) of parsed data.
	 * Indexed from zero, errorneous rows has no number.
	 */
	function getData($row, $col = null) {
		$data = $this->data[$row];
		if($col!==null) {
			$index= $this->fieldIndex($col);
			$data = $data[$index];
		}
		return $data;
	}

	/**
	 * Check if there is any error
	 *
	 * When no parameter is specified, result is related to all data.
	 * Check can be performed on a line or in combination with a fieldwith
	 *
	 * @param integer $line
	 * @param integer|string $field
	 */
	function hasError($line = null, $field = null) {
		if($line === null) {
			return (bool) $this->errors;
		}
		if(!key_exists($line, $this->errors)) return false;
		return $field === null || key_exists($field, $this->errors[$line]);
	}

	function getError($line = null, $field = null) {
		if($line === null) {
			return $this->errors;
		}
		if(!key_exists($line, $this->errors)) return null;
		$err = $this->errors[$line];

		if($field === null) {
			return $err;
		} elseif($field === self::ROW) {
			foreach($err as $k => $v) {
				if($k>=0) { unset($err[$k]); };
			}
			return $err;
		} else {
			if(!key_exists($field, $err)) return null;
			return	$err[$field];
		}
	}

	/** How many erorors are there (on given line if $line !== null)?	**/
	function errorsCount($line = null) {
		if($line === null) {
			return count($this->errors);
		}
		return key_exists($line, $this->errors)?count($this->errors[$line]):0;
	}

	/** Count of valid rows**/
	function count() {
		return count($this->data);
	}

	/** Return index of given named field or null if no such field exists.*/
	function fieldIndex($field) {

		if(is_int($field)) {
			return $field;
		}
		if(is_array($field)) {
			return array_map(array($this, 'fieldIndex'), $field);
		}

		if(!key_exists($field, $this->reverse_header)) {
			return null;
		}

		return $this->reverse_header[$field];
	}

	/**
	 * Use FromData or FromFile instead.
	 *
	 * @param resource $stream
	 * @param array $options
	 * - read_header - read field names from header [default: true]
	 * - empty_value - value to return when it is empty
	 * - ...
	 */
	function __construct($stream, $options = array()) {

		$options += array(
			'delimiter' => ',',
			'quote' => '"',
			'unique' => array(),
			'required' => array(),
			'not_null' => array(),
			'allowed' => false,
			'allowed_regexp' => array(),
			'format' => array(),
			'skip_empty_lines' => true,
			'unique_field_names' => true,
			'lower_field_names' => true,
			'trim_field_names' => true,
			"read_header" => true,
			'empty_value' => array(),
			"check_fields_count" => true,
		);

		$this->options = $options;
		$this->errors = array();

		$this->empty_value = array();
			foreach($this->options['empty_value'] as $fname => $value) {
				$index = $this->fieldIndex($fname);
				if($index!==null) {
					$this->empty_value[$index] = $value;
				}
			}

		if ($options["read_header"]) {
			$this->readHeader($stream);
		}
		$this->readBody($stream);

		parent::__construct($this->data);
	}

	/**
	 * Read line from csv strem returning array of values
	 */
	protected function readline($stream) {
		return fgetcsv($stream, 0, $this->options['delimiter'], $this->options['quote']);
	}

	/**
	 * Read header of csv file
	 */
	protected function readHeader($stream) {
		$this->header = $this->readline($stream);
		if(!$this->header) {
			$this->reverse_header = $this->header = array();
			$this->addError(_('File is empty'), self::HEADER);
			return;
		}
		if($this->options['trim_field_names']) {
			$this->header=array_map('trim',$this->header);
		}

		if($this->options['lower_field_names']) {
			$this->header=array_map('mb_strtolower',$this->header);
		}
		$this->reverse_header = array_flip($this->header);

		if($this->options['unique_field_names'] && count($this->header) != count($this->reverse_header)) {
			$counts =  array_count_values($this->header);
			$missing = array_keys(array_filter($counts, function($v) { return $v>1;}));
			$this->addError(sprintf(_('V datech jsou stejné sloupce s názvy "%s"'), implode('", "', $missing) ));
		}
	}

	/** Read body of csv file**/
	protected function readBody($stream) {
		$this->data = array();
		$this->line_numbers = array();
		$ln = 2;
		while(!feof($stream)) {
				$line = $this->readline($stream);
				foreach($this->empty_value as $index => $value) {
					if($line[$index] === '') {
						$line[$index] = $value;
					}
				}
				if($line) {
					$this->data[] = $line;
					$this->line_numbers[] = $ln;
				}
				$ln ++;
		}

		if ($this->options["read_header"] && $this->options["check_fields_count"]) {
			$this->checkFieldsCount();
		}

		if($this->options['required']) {
			$this->checkFields($this->options['required'], 'checkRequired');
		}
		if($this->options['not_null']) {
			$this->checkFields($this->options['not_null'], 'checkNotNull',
				array('must_exists' => false) );
		}

		if($this->options['allowed']) {
			$this->checkAllowedFields($this->options['allowed'], $this->options['allowed_regexp']);
		}

		if($this->options['format']) {
			$formats = $this->options['format'];
			if(is_array($formats)) {
				foreach($formats as $name => $format) {
					$this->formatField($name, $format);
				}
			} else {
				foreach($this->header as $name) {
					$this->formatField($name, $formats);
				}
			}
		}

		if($this->options['unique']) {
			$this->checkFields($this->options['unique'], 'checkUnique',
				array('groups' => true, 'must_exists' => false)
				);
		}
	}

	/**
	 * Return formating and validating routine for given format.
	 *
	 * @see self::$FormatingRoutine
	 */
	protected function getFormatRoutine($format) {
		if(!key_exists($format, static::$FormatRoutines)) {
			return false;
		}
		$f = static::$FormatRoutines[$format];
		if(is_int($f)) {
			return function($v) use ($f) { $out = filter_var($v, $f, FILTER_NULL_ON_FAILURE ); return $out === null?self::$Error:$out; };
		} elseif(is_string($f)) {
			return function($v) use ($f) { $out = $f($v); return $out===false?self::$Error:$out; };
		} else {
			return $f;
		}
	}

	/** Validate column using given format **/
	function formatField($field, $format) {
		$index = $this->fieldIndex($field);
		if($index === null) {
			return;
		}

		$fce = $this->getFormatRoutine($format);
		if($fce === false) {
			$this->addError(sprintf(_('Neznámý formát %s.'), $format), self::HEADER, $index);
			return;
		}

		foreach($this->data as $k => &$d) {
			if(!key_exists($index, $d)) continue;
			$v =& $d[$index];
			if($format !== 'emptystring' && trim($v)=='') {
				$v = null;
				continue;
			}
			$out = $fce($v);
			if($out === self::$Error) {
				$this->addError(sprintf(_('Špatný formát dat.'), $format), $k, $index);
				continue;
			}
			$v = $out;
		}
	}

	/** Validate that there are only fields given in $fields **/
	function checkAllowedFields($names, $regexps){
		$fields = array_diff($this->header, $names);
		$fields = array_filter(
			$fields,
			function($v) use($regexps) {
				foreach($regexps as $re) {
					if(preg_match($re, $v)) return false;
				}
				return true;
			}
		);

		if($fields) {
			$this->addError(_('Sloupec není v seznamu povolených sloupců.'), self::HEADER, array_keys($fields));
		}
	}

	/**
	 * Validate that each row has same number of fields as header.
	 */
	function checkFieldsCount() {
		$cnt = count($this->header);
		$lines=array_filter($this->data, function($d) use($cnt) {return count($d) != $cnt;});
		if($this->options['skip_empty_lines']) {
			$lines = array_filter($lines, function($v) {return count($v)>1 || trim($v[0])!=='';});
		}
		$this->addError(_("Počet polí neodpovídá počtu polí v záhlaví"), array_keys($lines));
	}

	/**
	 * Common validation preroutine.
	 * can recieve list of field to check, and check existence of field before calling validating method $method.
	 */
	function checkFields($field, $method, $options = array()) {
		$options+=array(
			'must_exists' => true,
			'groups' => false,			//allow group of fields
		);

		if(is_array($field)) {

			if( $options['groups'] === 2 ) {
				$index = $this->fieldIndex($field);
				$findex = array_filter($index, function($v) {return $v!==null;});

				if($options['must_exists'] && count($findex) != count($index)) {
					$missing = implode(', ', array_diff_key($field, $findex));
					$this->addError(sprintf(_('Sloupce %s v datech chybí'), $missing));
					return;
				}
				if(!$findex) { return ; };
				$this->$method($field, $findex);
				return;
			}
			$options['groups'] *= 2;
			foreach($field as $f) {
				$this->checkFields( $f, $method, $options );
			}
			return;
		}

		$index = $this->fieldIndex($field);
		if($index === null) {
			if($options['must_exists']) {
				$this->addError(sprintf(_('Sloupec %s v datech chybí'), $field));
			}
			return;
		}
		$this->$method($field, $index);
	}

	/**
	 * Check that values in field exists.
	 */
	function checkRequired($field, $index) {
		$missing = array_filter( $this->data, function($d) use($index)
			{ return !key_exists($index, $d) || $d[$index] == '';} );
		$this->addError(sprintf(_("Pole %s musí být vyplněno."), $field), array_keys($missing), $index);
	}

	/**
	 * Check that values in field are not null.
	 *
	 * they can not exists at all
	 */
	function checkNotNull($field, $index) {
		$missing = array_filter( $this->data, function($d) use($index)
			{ return key_exists($index, $d) && $d[$index] == '';} );
		$this->addError(sprintf(_("Pole %s musí být vyplněno."), $field), array_keys($missing), $index);
	}

	/** Check, that given field or field groups are unique */
	function checkUnique($field, $index) {
		if(!is_array($index)) {
			$index = array($index);
			$field = array($field);
		}
		$indexes = array_flip($index);

		$values = array_map(function($a) use($indexes) {return implode("\0",array_intersect_key($a, $indexes));}, $this->data);
		$flipped = array_flip($values);


		if(count($values) > count($flipped)) {
			$missing = array_diff_key($values, array_flip($flipped));
			$out = '';

			$errors = 0;

			foreach($missing as $m) {
				$lines = array();
				$lnumbers = array();
				foreach($values as $k => $v) {
					if($v == $m) {
						$lines[] = $k;
						$lnumbers[] = $this->line_numbers[$k];
					}
				}
				$fieldname = implode(', ', $field);
				$err = sprintf("Unikátní hodnota v poli '%s' je duplikovaná na řádkách %s\n", $fieldname, implode(', ', $lines));
				$this->addError($err, $lines, $index);
			}
		}
	}

	/**
	 * Add error to given line.
	 *
	 * Error can be even set to a specified field.
	 * self::HEADER means general error
	 */
	function addError($error, $line = self::HEADER, $field=null) {
		if(is_array($line)) {
			foreach($line as $l) {
				$this->addError($error, $l, $field);
			}
			return;
		}

		if(is_array($field)) {
			foreach($field as $f) {
				$this->addError($error, $line, $f);
			}
			return;
		}

		if($field !== null) {
			$fi = $this->fieldIndex($field);
			if($fi === null) {
				$error= "$field: $error";
			}
			$field = $fi;
		}

		if(!key_exists($line, $this->errors)) {
			$this->errors[$line] = array();
		}
		if($field === null) {
			$this->errors[$line][min(array_keys($this->errors[$line]) + array(0) ) -1] = $error;
		} else {
			$this->errors[$line][$field] = $error;
		}
	}

	/**
	 * Iterate over values, formating output using given rules.
	 *
	 * Usage
	 * ```
	 * $this->format(array(
	 * 	'id' => 'integer',
	 * 	'created' => 'date:iso'
	 * 	....
	 * ));
	 * ```
	 *
	 * @see CsvFormattedOutput
	 **/

	function customFormat($format) {
		return new CsvFormattedOutput($this, $format);
	}

	/**
	 * Iterate over data, returning rows of formated data.
	 *
	 * @param string $rules
	 * - 'db' for db format
	 * - 'view' for output to 'html'
	 *
	 * @see CsvFormattedOutput
	 */
	function formated($rules) {
		if($rules == 'db') {
			$rules = array('datetime' => 'date:iso');
		}
		if($rules == 'view') {
			$rules = array('datetime' => 'date');
		}

		$format = array();
		foreach($this->options['format'] as $field => $type) {
				if(key_exists($type, $rules)) {
					$format[$field] = $rules[$type];
				}
		}
		return $this->customFormat($format);
	}

	function asAssociative($keys = null) {
		$out = array();

		if(is_null($keys)){
			$keys = $this->header;
		}else{
			$out[] = $this->associative_row($this->header,$keys);
		}

		foreach($this->data as $row) {
			$out[] = $this->associative_row($row,$keys);
		}

		return $out;

		//$out = new CsvAssociativeOutput($this);
		//return $out;
	}

	function asArray() {
		$out = $this->data;
		array_unshift($out,$this->header);

		return $out;
	}

	function getRow($index){
		$index = (int)$index;
		if($index==0){
			return $this->header;
		}
		$index--;
		return isset($this->data[$index]) ? $this->data[$index] : array();
	}

	function getColumn($index){
		$out = array();
		$out[] = isset($this->header[$index]) ? $this->header[$index] : "";
		foreach($this->data as $row){
			$out[] = isset($row[$index]) ? $row[$index] : "";
		}
		return $out;
	}

	function associative_row($row,$keys = null) {
		if(is_null($keys)){ $keys = $this->header; }
		$hc = count($keys);
		$row = array_slice(array_pad($row, $hc, ''),0,$hc);
		return array_combine($keys, $row);
	}
}
