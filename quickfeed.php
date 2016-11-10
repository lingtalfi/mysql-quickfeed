<?php



//--------------------------------------------
// CONFIG
//--------------------------------------------
// db connection info
$dbName = "fake_db";
$dbUser = "root";
$dbPass = "root";

// feed target configuration
$feedFile = "/path/to/your/file.txt"; // this file contains one "item" per line
$dbTable = "users";
$dbColumn = "pseudo";
$truncateTableBeforeStart = true;
$dbIsUtf8 = true;





//--------------------------------------------
// SCRIPT - you shouldn't edit below this line
//--------------------------------------------

try {

    $dbOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
    if(true===$dbIsUtf8){
        $dbOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'utf8'";
    }
    $conn = new PDO('mysql:host=localhost;dbname=' . $dbName, $dbUser, $dbPass, $dbOptions);

    if(true === $truncateTableBeforeStart){
        // http://stackoverflow.com/questions/5452760/truncate-foreign-key-constrained-table
        $conn->query("DELETE FROM $dbName.$dbTable");
        $conn->query("ALTER TABLE $dbName.$dbTable AUTO_INCREMENT = 1");
    }

    $lines = file($feedFile, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        echo $line . "<br>";
        $stmt = $conn->prepare("INSERT INTO $dbName.$dbTable ($dbColumn) VALUES (:name)");
        $stmt->bindParam(':name', $line);
        $stmt -> execute();
    }
    $conn = null;
} catch (PDOException $e) {
    echo "Error!: " . $e->getMessage() . "<br/>";
    die();
}