<?php
namespace qb {
use PDO;
	/**
	* The MIT License (MIT)
	* 
	* Copyright (c) [2014] Earl Swigert
	* 
	* Permission is hereby granted, free of charge, to any person obtaining a copy
	* of this software and associated documentation files (the "Software"), to deal
	* in the Software without restriction, including without limitation the rights
	* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	* copies of the Software, and to permit persons to whom the Software is
	* furnished to do so, subject to the following conditions:
	* 
	* The above copyright notice and this permission notice shall be included in all
	* copies or substantial portions of the Software.
	* 
	* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
	* SOFTWARE.
	*/

	/**
	 * QueryBuilder constructs SQL queries for you by calling functions and can make 
	 * conditional queries very easy because the structure can be created out of order
	 * and QueryBuilder will arrange it for you.
	 * 
	 * @author Earl Swigert
	 * 
	 * Basic usage: 
	 * <code>
	 * 	$query = QueryBuilder::create($myPDO);
	 * 	$query->select('*')->from('myTable')->where('1');
	 * 	$result = $query->call();
	 * </code>
	 *
	 * More complex usage:
	 * Which employees should you invite out to the bar after work and what do they do?  But give me less than a hundred of them and start with the youngest.
	 * <code>
	 * 	$query = QueryBuilder::create($myPDO);
	 * 	$query->select('CONCAT_WS(" ", eo.name_first, eo.name_last) as employee_name, eo.age, o.occupation_name')->
	 * 		join('occupation o', 'o.occupation_id = eo.occupation_id')->
	 * 		addSubQuery('eo')->
	 * 			select('e.name_first, e.name_last, e.age, eo.occupation_id')->from('employee_occupation')->
	 * 			join('employee e', 'e.age >= 21')->sort('e.age')->limit(0, 100);
	 * </code>
	 * 
	 */
require_once('QBQuery.php');

	class QueryBuilder {

		private $query;
		private $rootQuery;
		private $currentQuery;
		private $subQueryCount = 0;

		private $limitSet = false;

		private $connection;
		private $vars = array();

		public static function create($connection, $start = "") {

			$q = new QueryBuilder($connection, $start);

			return $q;
		}

		private function __construct($connection, $start) {
			$this->connection = $connection;
			$this->currentQuery = new QBQuery($start);
			$this->rootQuery = $this->currentQuery;
			return $this;
		}

		public function addSubQuery($alias, $start = "") {
			$this->subQueryCount++;
			$qbq = new QBQuery($start, true, $alias, $this->subQueryCount);
			$this->currentQuery->addSubQuery($qbq);
			$this->currentQuery = $qbq;
			return $this;
		}

		public function pre($content) {
			$this->currentQuery->pre($content);
			return $this;
		}

		public function select($vars) {
			$this->currentQuery->select($vars);
			return $this;
		}

		public function from($table) {
			$this->currentQuery->from($table);
			return $this;
		}

		public function join($table, $on, $type = "INNER") {
			$this->currentQuery->join($table, $on, $type);
			return $this;
		}

		public function where($condition) {
			$this->currentQuery->where($condition);

			return $this;
		}

		public function groupBy($condition) {
			$this->currentQuery->groupBy($condition);

			return $this;
		}

		public function orderBy($condition) {
			$this->currentQuery->orderBy($condition);

			return $this;
		}

		public function limit($offset = 0, $count = 100) {
			$this->currentQuery->limit($offset, $count);
			return $this;
		}

		public function getQuery($params = null) {
			$q = $this->rootQuery->call();
			if($params) {
				foreach($params as $key => $value) {
					echo $key . " => " . $value ."\n";
					$rep = is_string($value) ? $this->connection->quote($value) : $value;
					$q = preg_replace('/'.$key.'\b/', $rep, $q, 1);
				}
			}
			return $q;
		}

		public function prettyPrint($params = null) {
			echo "<pre>".$this->getQuery($params)."</pre>";
			return $this;
		}

		public function call($params = array(), $func = 'fetchAll') {

			if(count($this->vars) > 0) {
				foreach($this->vars as $var) {
					$this->connection->exec($var. ";");
				}
			}
			$this->query = $this->rootQuery->call();
			$statement = $this->connection->prepare($this->query);
			$statement->execute($params);
			$results = $statement->{$func}(PDO::FETCH_OBJ);
			return $results;
		}

		/// Fixes the iniablity to use 'and' and 'or' as a method name because it's a PHP reserved word

		public function __call($func, $args) {
			switch ($func) {
				case 'and':
					$this->currentQuery->a($args[0]);
					return $this;
				case 'or':
					$this->currentQuery->o($args[0]);
					return $this;
				break;
				default:
					trigger_error("Call to undefined method ".__CLASS__."::$func()", E_USER_ERROR);
				die ();
			}
		}


	}
}