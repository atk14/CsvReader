Change Log
==========

All notable changes to the CsvReader will be documented in this file.

## [1.1.2] - 2024-10-30

* c54e241 - Fixes for PHP8.3

## [1.1.1] - 2024-03-05

* a73e8e1 - Methods CsvReader::FromFile() and CsvReader::FromData() detect and skip BOM

## [1.1] - 2020-10-28

* CsvReader::getColumn($index) for $index >= column count returns [] (it was null before)
* Fixes

## [1.0.1] - 2020-10-10

* CsvReader was tagged as compatible with PHP>=5.4

## [1.0] - 2020-10-10

* First tagged release
