<?php
namespace CsvReader;

/**
 * Iterate imported csv as associative array (field_name => field_value)
 */
class CsvAssociativeOutput extends BaseCsvOutput {

	function current() {
		$out = parent::current();
		return $this->csv->associative_row($out);
	}
}
