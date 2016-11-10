Mysql QuickFeed
===================
2016-11-10




Php script to feed a table with fixtures from a text file.



Context
==========

At the early stages of development, you need to fill your database with some data.
Some of those can be generated randomly (like a table of users for instance), and in some cases you know by advance
the data that you want to put in it (for instance a table of countries).

This script helps with the second category: the data that you know in advance.

If you need random data, you might want to look at other tools like the [Bullsheet generator](https://github.com/lingtalfi/BullSheet).




How
===========

In this section, we discuss the following topics.


- fill a table with only one column (auto-incremented column doesn't count)
- fill a table with multiple columns (auto-incremented column doesn't count), no foreign keys
- fill a table with multiple columns (auto-incremented column doesn't count), with foreign keys
- fill multiple tables at once (batch)


For each of those topics, the preparation is the same:

- download the quick-feed.php script
- open it and customize the "db connection info" part of the CONFIG section
- then customize the "feed target configuration" part of the CONFIG section (read the topics if you don't know how to)
	- you also need to create the fixture file, obviously, but that's also covered in the topics
- last step: open the script in your browser, done


If you've succeeded, the script will display the list of inserted items.

The script will also shows you any errors.

Good luck!




fill a table with only one column (auto-incremented column doesn't count)
------------------------------------------


This technique works great for the cases where you have only one column in the table, for instance a list of countries or
a list of music styles, ...

Create a text file with a content like this (one item per line).

```txt
France
Germany
Spain
United States
Zimbabwe
```

Important: this will only work for a table with only ONE column.


Here is your configuration:

```php
$feedDir = null;
$feedFile = "/path/to/your/countries.txt"; // this file contains one "item" per line
$dbTable = "countries";
$dbColumn = "name";
$truncateTableBeforeStart = true;
```





fill a table with multiple columns (auto-incremented column doesn't count), no foreign keys
------------------------------------------

This technique works great with small tables which content is known in advance.
For instance the stuff members table.


Your fixture file uses the ## symbol (by default) as a separator between fields, like this:


```txt
clarisse        ##      clarisse_doe@gmail.com      ##      img/membre/clarisse.jpg
marie-pierre    ##      mp-du-37@me.com             ##      img/membre/marie-pierre.jpg
marion          ##      hachis-parmentier@yahoo.fr  ##      img/membre/marion.jpg
paul            ##      kerberos-934@yahoo.com      ##      img/membre/paul.jpg
```



And here is your config:

```php
$feedDir = null;
$feedFile = "/path/to/your/stuff_members.txt"; // this file contains one "item" per line
$dbTable = "stuff_members";
$dbColumn = ["pseudo", "email", "url_photo"];
$truncateTableBeforeStart = true;
$columnSeparator = '##';
```



fill a table with multiple columns (auto-incremented column doesn't count), with foreign keys
-----------------------------------------------------------------------------------------------

Now let's imagine that you have a "has many" relationship.

For this example, the tables and columns are described below:

- team
	- id
	- nom
- team_has_members
	- team_id
	- members_id
- members
	- id
	- pseudo
	- email
	- url_photo

In this example we are interested in filling the team_has_members table.

Also, we don't want to worry with the ids (because they might change), so instead, we use data that we know: the team's "nom" column and the members's pseudo column for instance.

Here is the fixture file:

```txt
komin >  ## 	clarisse
komin >  ## 	marion
komin >  ## 	paul
team 1   ## 	paul
team 1   ## 	marie-pierre
team 2   ## 	marie-pierre
team 2   ## 	clarisse
team 2   ## 	marion
team 3   ## 	marion
team 3   ## 	clarisse
team 3   ## 	marie-pierre
team 3   ## 	paul
```


As you can see, I used the teams's "nom" values on the left of the column separator (##), and the members's "pseudo" values on the right.


And here is your config:

```php
$feedDir = null;
$feedFile = "/pathto/your/fixtures/team_has_members.txt";
$dbTable = "team_has_members";
$dbColumn = ["team_id", "members_id"];
$truncateTableBeforeStart = true;
$columnSeparator = '##';
$fetchers = [
    "team_id" => 'team::id::nom',
    "members_id" => 'members::id::pseudo',
];
```

The new thing here is the fetchers variable.
It's an array, which keys are the values of the dbColumn array that we want to make a request from.

And the values are a custom syntax that represents the sql statement to make.

It consists of three fields separated with the double colon (::) symbol.


The three fields are the following: **targetTable::targetName::targetWhere**.

- targetTable: name of the table to fetch
- targetColumn: name of the column to fetch
- targetWhere: name of the column used in the where clause


The sql statement will look like this, approximately (but more secure):
```mysql	
SELECT targetColumn from targetTable WHERE targetWhere=$value
```	

And the result of this sql query will be used instead, so team 1 for instance will be replaced with whatever team's id matches the request:

```mysql
select id from team where nom='team1'
```





fill multiple tables at once (batch)
----------------------------------------

So now our tool starts to be useful, but it would be an easier workflow if we could simply put all those fixtures in one directory
and fill all the tables in one fell swoop.

This is possible.

Create a directory (called /pathto/fixtures/quick-feed in this section) and put all your quick feed's fixtures in it.

Now here is your config:

```php
$feedDir = "/pathto/fixtures/quick-feed ";
$columnSeparator = '##';
$config = [
    'team.txt' => [
        'dbColumn' => 'nom',
    ],
    'instruments.txt' => [
        'dbColumn' => 'nom',
    ],
    'members.txt' => [
        'dbColumn' => ["pseudo", "email", "url_photo"],
    ],
    'countries.txt' => [
        'dbColumn' => 'nom',
    ],
    'styles.txt' => [
        'dbTable' => 'musical_styles',
        'dbColumn' => 'nom',
    ],
    'team_has_members.txt' => [
        'dbColumn' => ["team_id", "members_id"],
        'fetchers' => [
			"team_id" => 'team::id::nom',
		    "members_id" => 'members::id::pseudo',
        ],
    ],
];
```









Related
============

If you need random data, please use [Bullsheet](https://github.com/lingtalfi/BullSheet) instead





