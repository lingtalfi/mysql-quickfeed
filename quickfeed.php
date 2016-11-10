<?php


//--------------------------------------------
// CONFIG
//--------------------------------------------
// db connection info
$dbName = "oui";
$dbUser = "root";
$dbPass = "root";
$dbIsUtf8 = true;


// feed target configuration
$feedDir = null;
$feedFile = "/path/to/your/countries.txt"; // this file contains one "item" per line
$dbTable = "countries";
$dbColumn = "name";
$truncateTableBeforeStart = true;


//--------------------------------------------
// SCRIPT - you shouldn't edit below this line
//--------------------------------------------
function error($msg)
{
    return '<span style="color: red">' . $msg . '</span>';
}


function insertArray(array $lines, string $dbTable, array $dbColumn, string $columnSeparator, array $fetchers = null)
{
    global $conn, $dbName;

    $nbColumns = count($dbColumn);


    // compute the column names
    $columns = array_map(function ($v) {
        return ':' . $v;
    }, $dbColumn);

    $sColumns = implode(', ', $columns);
    $sDbColumns = implode(', ', $dbColumn);

    foreach ($lines as $line) {

        // compute the values
        $values = explode($columnSeparator, $line);
        $values = array_map(function ($v) {
            return trim($v);
        }, $values);

        if ($nbColumns === count($values)) {
            $stmt = $conn->prepare("INSERT INTO $dbName.$dbTable ($sDbColumns) VALUES ($sColumns)");
            foreach ($dbColumn as $column) {
                $value = array_shift($values);


                if (is_array($fetchers) && array_key_exists($column, $fetchers)) {
                    $fetchStatement = $fetchers[$column];
                    $p = explode('::', $fetchStatement, 3);
                    if (3 === count($p)) {
                        $tmpTable = $p[0];
                        $tmpColumn = $p[1];
                        $tmpWhereColumn = $p[2];
                        $q = "select $tmpColumn from $dbName.$tmpTable where $tmpWhereColumn=:name";
                        $tmpStmt = $conn->prepare($q);
                        $tmpStmt->bindValue(':name', $value);
                        $tmpStmt->execute();
                        $result = $tmpStmt->fetch(PDO::FETCH_ASSOC);
                        $value = array_shift($result);

                    } else {
                        echo error("Invalid fetchStatement: $fetchStatement") . '<br>';
                        return;
                    }
                }


                $stmt->bindValue(':' . $column, $value);
            }
            $stmt->execute();
            echo $line . "<br>";
        } else {
            echo error("column count mismatch: $line") . '<br>';
        }
    }
}


try {

    $dbOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
    if (true === $dbIsUtf8) {
        $dbOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'utf8'";
    }
    $conn = new PDO('mysql:host=localhost;dbname=' . $dbName, $dbUser, $dbPass, $dbOptions);


    // compute config
    if (!isset($feedDir)) {
        $feedDir = dirname($feedFile);
        $fileName = basename($feedFile);

        $options = [
            'dbTable' => $dbTable,
            'dbColumn' => $dbColumn,
            'truncateTableBeforeStart' => $truncateTableBeforeStart,
        ];
        if (isset($fetchers)) {
            $options['fetchers'] = $fetchers;
        }
        $config = [
            $fileName => $options,
        ];
    }


    foreach ($config as $fileName => $configItem) {

        echo '<h3>' . $fileName . '</h3>';
        // set dbTable
        $dbTable = null;
        if (!array_key_exists('dbTable', $configItem)) {
            $dbTable = pathinfo($fileName, PATHINFO_FILENAME);
        } else {
            $dbTable = $configItem['dbTable'];
        }

        $truncateTableBeforeStart = $configItem['truncateTableBeforeStart'] ?? true;
        $dbColumn = $configItem['dbColumn'];

        // set fetchers
        $fetchers = $configItem['fetchers'] ?? null;


        if (true === $truncateTableBeforeStart) {
            // http://stackoverflow.com/questions/5452760/truncate-foreign-key-constrained-table
            $conn->query("DELETE FROM $dbName.$dbTable");
            $conn->query("ALTER TABLE $dbName.$dbTable AUTO_INCREMENT = 1");
        }

        $feedFile = $feedDir . "/" . $fileName;
        $lines = file($feedFile, FILE_IGNORE_NEW_LINES);


        if (is_string($dbColumn)) {
            foreach ($lines as $line) {
                echo $line . "<br>";
                $stmt = $conn->prepare("INSERT INTO $dbName.$dbTable ($dbColumn) VALUES (:name)");
                $stmt->bindValue(':name', $line);
                $stmt->execute();
            }
        } else if (is_array($dbColumn)) {
            insertArray($lines, $dbTable, $dbColumn, $columnSeparator, $fetchers);
        }
    }


    $conn = null;
} catch (PDOException $e) {
    echo error("Error!: " . $e->getMessage()) . "<br/>";
    die();
}