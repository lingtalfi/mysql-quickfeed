<?php


class QuickFeed
{

    private $dbName;
    private $dbUser;
    private $dbPass;
    private $dbIsUtf8;

    private $feedDir;

    private $feedFile;
    private $dbTable;
    private $dbColumn;
    private $fetchers;


    private $columnSeparator;
    private $truncateTableBeforeStart;


    private $config;




    // tmp
    private $conn;




    public function __construct(array $options)
    {
        $this->dbName = $options['dbName'];
        $this->dbUser = $options['dbUser'];
        $this->dbPass = $options['dbPass'];
        $this->dbIsUtf8 = $options['dbIsUtf8'];


        $this->feedDir = (array_key_exists('feedDir', $options)) ? $options['feedDir'] : null;


        $this->feedFile = (array_key_exists('feedFile', $options)) ? $options['feedFile'] : null;
        $this->dbTable = (array_key_exists('dbTable', $options)) ? $options['dbTable'] : null;
        $this->dbColumn = (array_key_exists('dbColumn', $options)) ? $options['dbColumn'] : null;
        $this->fetchers = (array_key_exists('fetchers', $options)) ? $options['fetchers'] : null;



        $this->config = (array_key_exists('config', $options)) ? $options['config'] : [];

        $this->columnSeparator = (array_key_exists('columnSeparator', $options)) ? $options['columnSeparator'] : '##';
        $this->truncateTableBeforeStart = (array_key_exists('truncateTableBeforeStart', $options)) ? $options['truncateTableBeforeStart'] : true;

    }

    public function feed()
    {

        try {

            $feedDir = $this->feedDir;
            $config = $this->config;

            //--------------------------------------------
            // INITIATE DB CONNECTION
            //--------------------------------------------
            $dbOptions = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ];
            if (true === $this->dbIsUtf8) {
//            $dbOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'utf8'";
                // with the option below, mysql doesn't complain about the default values not being set, it just fills the tables
                // http://stackoverflow.com/questions/15438840/mysql-error-1364-field-doesnt-have-a-default-values
                $dbOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET sql_mode=(SELECT REPLACE(@@sql_mode,'STRICT_TRANS_TABLES','')), NAMES 'utf8'";
            }
            $this->conn = new PDO('mysql:host=localhost;dbname=' . $this->dbName, $this->dbUser, $this->dbPass, $dbOptions);

            //--------------------------------------------
            // COMPUTE CONFIG
            // we want to create a config array which contains all configurations, one configuration per file
            // The user can specify either:
            // - feedDir: to parse all the files in the given directory
            // - feedFile: to parse a file in particular
            //
            // In both cases, codewise, we want to deal with one $config variable only, so that we can loop on it
            //--------------------------------------------
            if (null === $feedDir) {
                $feedDir = dirname($this->feedFile);
                $fileName = basename($this->feedFile);

                $options = [
                    'dbTable' => $this->dbTable,
                    'dbColumn' => $this->dbColumn,
                    'truncateTableBeforeStart' => $this->truncateTableBeforeStart,
                ];
                if (isset($this->fetchers)) {
                    $options['fetchers'] = $this->fetchers;
                }
                $config = [
                    $fileName => $options,
                ];
            }


            //--------------------------------------------
            // ITERATING THE CONFIG ARRAY
            // A configItem contains the info necessary to feed a table
            //--------------------------------------------
            foreach ($config as $fileName => $configItem) {

                echo '<h3>' . $fileName . '</h3>';
                // set dbTable
                $dbTable = null;
                if (!array_key_exists('dbTable', $configItem)) {
                    $dbTable = pathinfo($fileName, PATHINFO_FILENAME);
                } else {
                    $dbTable = $configItem['dbTable'];
                }

                $truncateTableBeforeStart = $configItem['truncateTableBeforeStart'] ?? $this->truncateTableBeforeStart;
                $dbColumn = $configItem['dbColumn'];


                if (true === $truncateTableBeforeStart) {
                    // http://stackoverflow.com/questions/5452760/truncate-foreign-key-constrained-table
                    $fullTable = $this->dbName . "." . $dbTable;
                    $this->conn->query("DELETE FROM $fullTable");
                    $this->conn->query("ALTER TABLE $fullTable AUTO_INCREMENT = 1");
                }

                $feedFile = $feedDir . "/" . $fileName;
                $lines = file($feedFile, FILE_IGNORE_NEW_LINES);

                if (is_string($dbColumn)) {
                    foreach ($lines as $line) {
                        echo $line . "<br>";
                        $stmt = $this->conn->prepare("INSERT INTO $fullTable ($dbColumn) VALUES (:name)");
                        $stmt->bindValue(':name', $line);
                        $stmt->execute();
                    }
                } else if (is_array($dbColumn)) {
                    $this->insertArray($lines, $dbTable, $dbColumn, $this->columnSeparator, $configItem);
                }
            }


            $this->conn = null;
        } catch (PDOException $e) {
            echo $this->error("Error!: " . $e->getMessage()) . "<br/>";
            die();
        }

    }

    //--------------------------------------------
    // PRIVATE
    //--------------------------------------------

    private function insertArray(array $lines, string $dbTable, array $dbColumn, string $columnSeparator, array $configItem)
    {


        // set fetchers
        $fetchers = $configItem['fetchers'] ?? null;


        $defaults = (array_key_exists('defaults', $configItem)) ? $configItem['defaults'] : [];


        foreach($defaults as $k => $v){
            $dbColumn[] = $k;
        }
        $nbColumns = count($dbColumn);

        // compute the column names
        $columns = array_map(function ($v) {
            return ':' . $v;
        }, $dbColumn);

        $sColumns = implode(', ', $columns);
        $sDbColumns = implode(', ', $dbColumn);
        $fullTable = $this->dbName . "." .$dbTable;

        foreach ($lines as $line) {

            // compute the values
            $values = explode($columnSeparator, $line);
            $values = array_map(function ($v) {
                return trim($v);
            }, $values);

            foreach($defaults as $v){
                $values[] = $v;
            }


            if ($nbColumns === count($values)) {
                $stmt = $this->conn->prepare("INSERT INTO $fullTable ($sDbColumns) VALUES ($sColumns)");
                foreach ($dbColumn as $column) {
                    $value = array_shift($values);


                    if (is_array($fetchers) && array_key_exists($column, $fetchers)) {
                        $fetchStatement = $fetchers[$column];
                        $p = explode('::', $fetchStatement, 3);
                        if (3 === count($p)) {
                            $tmpTable = $p[0];
                            $tmpColumn = $p[1];
                            $tmpWhereColumn = $p[2];
                            $fullTmpTable = $this->dbName . "." . $tmpTable;
                            $q = "select $tmpColumn from $fullTmpTable where $tmpWhereColumn=:name";
                            $tmpStmt = $this->conn->prepare($q);
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
                echo $this->error("column count mismatch: $line") . '<br>';
            }
        }
    }


    private function error($msg)
    {
        return '<span style="color: red">' . $msg . '</span>';
    }
}