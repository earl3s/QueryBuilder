<html>
<head>
	<title>Example usage of QueryBuilder</title>
	<link rel="stylesheet" href="http://cdnjs.cloudflare.com/ajax/libs/highlight.js/8.1/styles/default.min.css">
	<script src="http://cdnjs.cloudflare.com/ajax/libs/highlight.js/8.1/highlight.min.js"></script>
	<script>hljs.initHighlightingOnLoad();</script>

	<style>
		body {
			font-family:sans-serif;
		}

		.container {
			width: 960px;
			margin: auto;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>Query Builder Examples</h1>
		<h2>A query creation tool</h2>
		<p>Query Builder is not designed to write insertion queries, update queries, or delete queries.  It is designed to help with complex selection queries.</p>
	<?php

		require('qb/QueryBuilder.php');
		use qb\QueryBuilder;

	?>
		<h3>Query Builder in action</h3>
		<pre><code class='php'>
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
		 </code></pre>

		 <h4>Becomes:</h4>
	<?php
		echo "<pre><code class='mysql'>";
		//$db = new PDO('mysql:host=localhost;dbname=test;charset=utf8', 'db_user', 'db_pass');
		$db = '';
		$query = QueryBuilder::create($db);
		$query->select('CONCAT_WS(" ", eo.name_first, eo.name_last) as employee_name, eo.age, o.occupation_name')->
		 	join('occupation o', 'o.occupation_id = eo.occupation_id')->
		 	addSubQuery('eo')->
		 		select('e.name_first, e.name_last, e.age, eo.occupation_id')->from('employee_occupation eo')->
		 		join('employee e', 'e.age >= 21')->orderBy('e.age')->limit(0, 100);
		$query->prettyPrint();
		echo "</code></pre>"
?>
	<h3>Where Query Builder shines</h3>
	<p>Query Builder stands out when you have to do things like add conditional subqueries, joins, or write long queries.  A great example of this is conditional filtering through subqueries.</p>
	<pre><code class='php'>
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
	</code></pre>
	<h4>Becomes:</h4>
	<?php
		echo "<pre><code class='mysql'>";
		//$db = new PDO('mysql:host=localhost;dbname=test;charset=utf8', 'db_user', 'db_pass');
		
		function withShift($isShiftFiltered) {
			$db = '';
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

		 	return $query;
		}
		withShift(true)->prettyPrint();
		echo "</code></pre>";
		echo "<h4>Or:</h4>";
		echo "<pre><code class='mysql'>";
		withShift(false)->prettyPrint();
		echo "</code></pre>";
?>
	<p>Depending on whether or not <code style="color:#333">$isFiltered</code> is true or not.  We don't have to worry about parenthesis or ugly string concatenation.  Query Builder will create beautiful output for us.  There is even a <code style="color:#333">prettyPrint</code> function will print out your query for you if you want to see what you've created laid out nicely for debugging.</p>
	</div>
</body>
</html>