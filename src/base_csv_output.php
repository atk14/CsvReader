<?php
namespace CsvReader;

/**
 * Base class for iterating imported csv. Can skip empty lines.
 */
class BaseCsvOutput extends \FilterIterator {

	function __construct($csvimport) {
		$this->csv = $csvimport;
		parent::__construct($csvimport);
	}

	function accept() {
		$out =
			!$this->csv->options['skip_empty_lines'] ||
			count(\FilterIterator::current()) > 1 ||
			trim(\FilterIterator::current()[0]) != '';
		return $out;
	}
}
