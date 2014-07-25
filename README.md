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

###Becomes:

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

###Becomes:

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
###Or:

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