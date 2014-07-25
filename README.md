QueryBuilder
============

##A query creation tool

Query Builder is not designed to write insertion queries, update queries, or delete queries. It is designed to help with complex selection queries.
Query Builder uses PDO as it's query engine so it will work with MySQL, SQLLite, Postgres, SQLServer, or any other database engine supported by PDO.

##Query Builder in action

```php
require('qb/QueryBuilder.php');
use qb\QueryBuilder;

$db = new PDO('mysql:host=localhost;dbname=test;charset=utf8', 'db_user', 'db_pass');
$query = QueryBuilder::create($db);
$query->select('CONCAT_WS(" ", eo.name_first, eo.name_last) as employee_name, eo.age, o.occupation_name')->
	join('occupation o', 'o.occupation_id = eo.occupation_id')->
	addSubQuery('eo')->
		select('e.name_first, e.name_last, e.age, eo.occupation_id')->from('employee_occupation')->
		join('employee e', 'e.age >= 21')->orderBy('e.age')->limit(0, 100);
$query->call();
```

####Becomes:

```mysql
SELECT CONCAT_WS(" ", eo.name_first, eo.name_last) as employee_name, eo.age, o.occupation_name 
  FROM 
	( SELECT e.name_first, e.name_last, e.age, eo.occupation_id 
	  FROM employee_occupation eo 
	  INNER JOIN employee e ON e.age >= 21
	  ORDER BY e.age 
	  LIMIT 0, 100	) AS eo 
INNER JOIN occupation o ON o.occupation_id = eo.occupation_id
```

###Query Builder will format your query for you
Query Builder knows what you want to do so it will order your SQL for you.

```php
$query->select('*')->join('table2', 'table2.a = table1.a')->from('table1')->limit('0', '10')->groupBy('table.b');
```
and 
```php
$query->select('*')->from('table1')->groupBy('table.b')->join('table2', 'table2.a = table1.a')->limit('0', '10');
```
will both output

```mysql
SELECT * 
FROM table1 
INNER JOIN table2 ON table2.a = table1.a
GROUP BY table1.b 
LIMIT 0, 10
```

##Where Query Builder shines

Query Builder stands out when you have to do things like add conditional subqueries, joins, or write long queries. A great example of this is conditional filtering through subqueries.

```php
require('qb/QueryBuilder.php');
use qb\QueryBuilder;

$query = QueryBuilder::create($db);
$query->select('CONCAT_WS(" ", eo.name_first, eo.name_last) as employee_name, eo.age, o.occupation_name')->
 	join('occupation o', 'o.occupation_id = eo.occupation_id')->
 	addSubQuery('eo')->
 		select('e.name_first, e.name_last, e.age, eo.occupation_id')->
 		join('employee e', 'e.age >= :age')->and('e.is_single = 1')->orderBy('e.age')->limit(0, 100);

 	if($isShiftFiltered) {
 		$query->join('employee_occupation eo', 'eo.employee_id = q.employee_id')->
 		addSubQuery('q')->select('es.employe_id')->from('employee_shift es')->
 		where('es.shift = "the_late_shift"');
 	}
 	else {
 		$query->from('employee_occupation q');
 	}
$query->call(array(':age'=>21));
```

####Becomes:

```mysql
SELECT CONCAT_WS(" ", eo.name_first, eo.name_last) as employee_name, eo.age, o.occupation_name 
  FROM 
	( SELECT e.name_first, e.name_last, e.age, eo.occupation_id 
	  FROM 
		( SELECT es.employe_id 
		  FROM employee_shift es 
		  WHERE es.shift = "the_late_shift" 
		) AS q 
	  INNER JOIN employee e ON e.age >= :age
	  AND e.is_single = 1
	  INNER JOIN employee_occupation eo ON eo.employee_id = q.employee_id
	  ORDER BY e.age 
	  LIMIT 0, 100	) AS eo 
INNER JOIN occupation o ON o.occupation_id = eo.occupation_id
```
####Or:

```mysql
SELECT CONCAT_WS(" ", eo.name_first, eo.name_last) as employee_name, eo.age, o.occupation_name 
  FROM 
	( SELECT e.name_first, e.name_last, e.age, eo.occupation_id 
	  FROM employee_occupation q 
	  INNER JOIN employee e ON e.age >= :age
	  AND e.is_single = 1
	  ORDER BY e.age 
	  LIMIT 0, 100	) AS eo 
INNER JOIN occupation o ON o.occupation_id = eo.occupation_id
```

The output changes based on whether not `$isFiltered` is `true` or not. We don't have to worry about parenthesis or ugly string concatenation. Query Builder will create beautiful output for us. There is even a `prettyPrint` function will print out your query for you if you want to see what you've created laid out nicely for debugging.

##Capabilities

Query builder has support for the following features:

* Parameters through PDO
    * Parameters are passed through the `call` function like they would be in `PDO::execute`
* SELECT
    * `$query->select('*')`
* FROM
	* `$query->from('myTable mt')`
* WHERE
	* `$query->where('x = 1')->and('y = 2')` You write your own `WHERE` clause so it will handle anythign SQL can do.  `IN`, `<,` `=,` `>`, `BETWEEN`, or anything else you need.
* GROUP BY
	* `$query->groupBy('table.a')`
* ORDER BY
	* `$query->orderBy('table.b DESC')`
* LIMIT
	* `$query->limit('0', '100')` or `$query->limit(':offset', ':count')` if you want to use pagination in a query.
* JOINS
	* `$query->join('table1 t1', 't1.a = tt2.a')` `INNER` is the default join type.  You can change the join type by specifying that as the third parameter to the function.  You can specify by typing the full join type, or use `'l'` for `LEFT OUTER`, `'r'` for `RIGHT OUTER`, or `'f'` for `FULL OUTER`.
* AND & OR
	* There are specific functions for AND and OR and they work in any context you are in.  This includes JOINS and WHERE claues. `$query->where('a = 3')->and('b > 5')->or('b < 2')`.
* Sub Queries
	* `$query->addSubQuery('subQueryAlias')->select('*')->from('table')` You can add any number of sub queries and Query Builder will format them and add the correct number of closing parenthesis for you.  Easy peasy.
* HAVING
	* `$query->having('rowCount > 100')`

####Other functions:

* `call($params)` will send the query to the database.  `call` optionally takes an associative array of parameters for PDO to use. So: 

```php
$query = QueryBuilder::create($pdo);
$query->select('*')->from('people')->where('age > :age ');
$query->call(array(':age'=>18));
```
* `prettyPrint` will print the query to the screen for debugging.  This includes nicely tabbed formatting for easy reading.
* `QueryBuilder::create` is a factory method for creating a new instance of QueryBuilder.  It's setup this way so that future versions could include global presets for every query through static properties.

###Licence

Query Builder is licensed under the MIT license so please use it, and feel free to contribute.