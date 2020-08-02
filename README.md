CsvReader
=========

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
    // $reader = CsvReader::FromFile("path/to/file.csv");
    
    $reader->getTotalRowCount(); // 5
    $reader->getTotalColumnCount(); // 3

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

Installation
------------

    composer require atk14/cvs-reader dev-master

Testing
-------

    composer update --dev
    ./vendor/bin/run_unit_tests test

[//]: # ( vim: set ts=2 et: )
