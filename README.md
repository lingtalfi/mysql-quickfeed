Mysql QuickFeed
===================
2016-11-10




Php script to feed a table with a text file.




How
------------

Create a text file with a content like this (one item per line).

```txt
France
Germany
Spain
United States
Zimbabwe
```


Then configure the quickfeed.php script and launch it in a web browser: it will feed the configured table with those countries.


Example configuration (quickfeed.php)

```php
//--------------------------------------------
// CONFIG
//--------------------------------------------
// db connection info
$dbName = "fake_db";
$dbUser = "root";
$dbPass = "root";

// feed target configuration
$feedFile = "/path/to/your/countries.txt"; // this file contains one "item" per line
$dbTable = "countries";
$dbColumn = "name";
$truncateTableBeforeStart = true;
$dbIsUtf8 = true;
```



