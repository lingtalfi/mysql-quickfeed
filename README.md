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

There are different things that you can do with the QuickFeed class.



- fill a table with only one column (auto-incremented column doesn't count)
- fill a table with multiple columns (auto-incremented column doesn't count), no foreign keys
- fill a table with multiple columns (auto-incremented column doesn't count), with foreign keys
- fill multiple tables at once (batch)




fill a table with only one column (auto-incremented column doesn't count)
------------------------------------------

Let's say you have a table named countries, with the following fields

- id (auto-incremented)
- name


QuickFeed can help you feed this table.

Create a text file named countries.txt with a content like this (one item per line).

```txt
France
Germany
Spain
United States
Zimbabwe
```



Then execute the following script to feed your table.



```php
<?php


require_once __DIR__ . "/QuickFeed.php";


$q = new QuickFeed([

    /**
     * Database dns and related parameters
     */
    'dbName' => 'oui',
    'dbUser' => 'root',
    'dbPass' => 'root',
    'dbIsUtf8' => true,
    /**
     * Other default parameters and their default values.
     * You can actually omit those unless you need to change their values.
     */
    'columnSeparator' => '##',
    'truncateTableBeforeStart' => true,
    //--------------------------------------------
    // Below are the 'real' parameters
    //--------------------------------------------
    'feedFile' => 'countries.txt',
    'dbTable' => 'countries',
    'dbColumn' => 'name',
]);
$q->feed();

```


Note: the countries table will be truncated before it is fed.
If you prefer to keep the existing data, set the truncateTableBeforeStart option to false.




fill a table with multiple columns (auto-incremented column doesn't count), no foreign keys
------------------------------------------

In this case, there is a table named stuff_members, which contains the following columns:

- id (auto-incremented)
- pseudo
- email
- url_photo


To feed this table, put each row's data on one line, and separate the columns with the double
sharp symbol (##), like this:


```txt
clarisse        ##      clarisse_doe@gmail.com      ##      img/membre/clarisse.jpg
marie-pierre    ##      mp-du-37@me.com             ##      img/membre/marie-pierre.jpg
marion          ##      hachis-parmentier@yahoo.fr  ##      img/membre/marion.jpg
paul            ##      kerberos-934@yahoo.com      ##      img/membre/paul.jpg
```


Note: you can change the separator using the columnSeparator option.


Then execute the following script to feed your table.

```php
<?php


require_once __DIR__ . "/QuickFeed.php";


$q = new QuickFeed([

    /**
     * Database dns and related parameters
     */
    'dbName' => 'oui',
    'dbUser' => 'root',
    'dbPass' => 'root',
    'dbIsUtf8' => true,
    /**
     * Other default parameters and their default values.
     * You can actually omit those unless you need to change their values.
     */
    'columnSeparator' => '##',
    'truncateTableBeforeStart' => true,
    //--------------------------------------------
    // Below are the 'real' parameters
    //--------------------------------------------
    'feedFile' => 'stuff_members.txt',
    'dbTable' => 'stuff_members',
    'dbColumn' => ["pseudo", "email", "url_photo"],
]);
$q->feed();

```



fill a table with multiple columns (auto-incremented column doesn't count), with foreign keys
-----------------------------------------------------------------------------------------------

Now let's imagine that you have a "has many" relationship.

For this example, imagine that our schema looks like this:

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

Also, we don't want to deal with the ids, because they might change in the future, and they are not human friendly.

Instead, we want to use the team's "nom" column and the members's pseudo column, which are more reliable, and more human friendly.

Here is our team_has_members.txt file.

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


Now we need to tell QuickFeed how to parse our file.


```php
<?php


require_once __DIR__ . "/QuickFeed.php";


$q = new QuickFeed([

    /**
     * Database dns and related parameters
     */
    'dbName' => 'oui',
    'dbUser' => 'root',
    'dbPass' => 'root',
    'dbIsUtf8' => true,
    /**
     * Other default parameters and their default values.
     * You can actually omit those unless you need to change their values.
     */
    'columnSeparator' => '##',
    'truncateTableBeforeStart' => true,
    //--------------------------------------------
    // Below are the 'real' parameters
    //--------------------------------------------
    'feedFile' => 'team_has_members.txt',
    'dbTable' => 'team_has_members',
    'dbColumn' => ["team_id", "members_id"],
    'fetchers' => [
        "team_id" => 'team::id::nom',
        "members_id" => 'members::id::pseudo',
    ],
]);
$q->feed();

```


The new thing here is the fetchers option.

It indicates how to replace the members of the dbColumn option (team_id and members_id in this case).

The keys represent the the dbColumn member to replace.


The values indicate how to replace it.
It uses a custom syntax that represents a sql statement.

It consists of three fields separated with the double colon (::) symbol.


The three fields are the following: **targetTable::targetName::targetWhere**.

- targetTable: name of the table to fetch
- targetColumn: name of the column to fetch
- targetWhere: name of the column used in the where clause


The symbolic resulting sql statement will look like this (to give you an idea of what's going on):
```mysql	
SELECT targetColumn from targetTable WHERE targetWhere=$value
```	

And the result of this sql query will be used instead, so "team 1" for instance will be replaced with whatever team's id matches
the following request:

```mysql
select id from team where nom='team 1'
```





fill multiple tables at once (batch)
----------------------------------------


So far, we've been working on a per table basis.

While this is fine to understand how QuickFeed works, in a real world scenario you will generally want to
feed multiple tables in one pass.

QuickFeed can do that too!

You need to create a directory (named quick-feed thereafter), and put all your fixtures files in it.


When this is done, you then need to indicate which files you want to process.

This example should be self-explaining.




```php
<?php


require_once __DIR__ . "/QuickFeed.php";


$q = new QuickFeed([

    /**
     * Database dns and related parameters
     */
    'dbName' => 'oui',
    'dbUser' => 'root',
    'dbPass' => 'root',
    'dbIsUtf8' => true,
    /**
     * Other default parameters and their default values.
     * You can actually omit those unless you need to change their values.
     */
    'columnSeparator' => '##',
    'truncateTableBeforeStart' => true,
    //--------------------------------------------
    // Below are the 'real' parameters
    //--------------------------------------------
    'feedDir' => '/path/to/quick-feed',
    'config' => [
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
    ],
]);
$q->feed();

```


The novelty here is the config option.

It's an array which keys are the filename to parse,
and which values are an option array (same as what we've seen in the previous examples).


### Using default values

It's also possible to add your own default values, using the 'defaults' option.
Below is a real life example illustrating that.



```php
$q = new QuickFeed([
    'dbName' => 'oui',
    'dbUser' => 'root',
    'dbPass' => 'root',
    'dbIsUtf8' => true,
    'truncateTableBeforeStart' => false,
    'feedDir' => "path/to/quick-feed",
    'columnSeparator' => '##',
    'config' => [
        'users.txt' => [
            'dbColumn' => ["email", "pseudo", "password"],
            'defaults' => [
                'active' => 1,
                'url_photo' => url('/img/site/default-user.jpg'),
                'nom' => '',
                'prenom' => '',
                'sexe' => 'h',
                'date_naissance' => null,
                'code_postal' => "",
                'ville' => "",
                'pays_id' => null,
                'niveaux_id' => null,
                'biographie' => "",
                'influences' => "",
                'prochains_concerts' => "",
                'site_internet' => "",
                'newsletter' => "n",
                'show_sexe' => "n",
                'show_date_naissance' => "n",
                'show_niveau' => "n",
            ],
        ],
    ],
]);
$q->feed();
```





Related
============

If you need random data, please use [Bullsheet](https://github.com/lingtalfi/BullSheet) instead







Log
==============

- 2016-11-16: refactored the script in a class, added defaults option
- 2016-11-10: first strike




