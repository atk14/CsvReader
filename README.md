CsvReader
=========

Reads CSV data from a file or a string. Detects automatically CSV format parameters.

Basic usage
-----------

    $csv_data = '
    k1;k2;k3
    v1;v2;v3
    x1;x2;x3
    y1;y2
    z1
    ';
    $csv_data = trim($csv_data);

    $reader = CsvReader::FromData($csv_data);
    // or
    $reader = CsvReader::FromFile("path/to/file.csv");
    
    $reader->getRowCount(); // 5
    $reader->getColumnCount(); // 3

    $rows = $reader->getRows();
    // [
    //  ["k1","k2","k3"],
    //  ["v1","v2","v3"],
    //  ["x1","x2","x3"],
    //  ["y1","y2",""],
    //  ["z1","",""],
    // ]

    $rows = $reader->getRows(["offset" => 2]);
    // [
    //  ["x1","x2","x3"],
    //  ["y1","y2",""],
    //  ["z1","",""],
    // ]

    $row = $reader->getRow(0);
    // ["k1","k2","k3"]

    $row = $reader->getRow(1);
    // ["v1","v2","v3"]

    $col = $reader->getColumn(0);
    // ["k1","v1","x1","y1","z1"],

    $col = $reader->getColumn(0,["offset" => 1]);
    // ["v1","x1","y1","z1"],

    $col = $reader->getColumn(2);
    // ["k3","v3","x3","",""],

    $col = $reader->getColumn(3);
    // null

    $rows = $reader->getAssociativeRows();
    // [
    //  ["k1" => "v1", "k1" => "v2", "k3" => "v3"],
    //  ["k1" => "x1", "k1" => "x2", "k3" => "x3"],
    //  ["k1" => "y1", "k1" => "y2", "k3" => ""],
    //  ["k1" => "z1", "k1" => "", "k3" => ""],
    // ]

    $rows = $reader->getAssociativeRows(["header_line" => 1]); // counting from 0, so this is the 2nd line
    // [
    //  ["v1" => "x1", "v1" => "x2", "v3" => "x3"],
    //  ["v1" => "y1", "v1" => "y2", "v3" => ""],
    //  ["v1" => "z1", "v1" => "", "v3" => ""],
    // ]

    $rows = $reader->getAssociativeRows(["keys" => ["f1","f2","f3"]]);
    // [
    //  ["f1" => "k1", "k1" => "k2", "k3" => "k3"],
    //  ["f1" => "v1", "k1" => "v2", "k3" => "v3"],
    //  ["f1" => "x1", "k1" => "x2", "k3" => "x3"],
    //  ["f1" => "y1", "k1" => "y2", "k3" => ""],
    //  ["f1" => "z1", "k1" => "", "k3" => ""],
    // ]

    $rows = $reader->getAssociativeRows(["keys" => ["f1","f2","f3"], "offset" => 2]);
    // [
    //  ["f1" => "x1", "k1" => "x2", "k3" => "x3"],
    //  ["f1" => "y1", "k1" => "y2", "k3" => ""],
    //  ["f1" => "z1", "k1" => "", "k3" => ""],
    // ]

CSV format parameters auto-detection
------------------------------------

The CsvReader detects format parameters automatically. If the auto-detection fails, format parameters can be specified explicitly.


    $reader = CsvReader::FromData($csv_data,[
      "delimiter" => ";",
      "quote" => "'"
    ]);
    // or
    $reader = CsvReader::FromFile("path/to/file.csv",[
      "delimiter" => ";",
      "quote" => "'"
    ]);

Installation
------------

    composer require atk14/cvs-reader dev-master

Testing
-------

    composer update --dev
    ./vendor/bin/run_unit_tests test

[//]: # ( vim: set ts=2 et: )
