<?php
namespace qb {

	/**
	* The MIT License (MIT)
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
	 * Used to construct Queries through the QueryBuilder
	 * @author Earl Swigert
	 */
	class QBQuery {
		
		private $isSubquery;
		private $subQuery;
		private $query;
		private $start;
		private $hasSubQuery = false;
		private $level;
		private $last;

		private $alias = "";
		private $select = "";
		private $from = "";
		private $where = "";
		private $groupBy = "";
		private $having = "";
		private $orderBy = "";
		private $limit = "";
		private $pre = "";
		private $joins = array();

		function __construct($start = "", $isSubquery = false, $alias = "", $level = 0) {
			$this->isSubquery = $isSubquery;
			$this->start = $start;
			$this->alias = $alias;
			$this->level = $level;
		}

		public function addSubQuery($subQuery) {
			$this->hasSubQuery = true;
			$this->subQuery = $subQuery;
			return $this;
		}

		public function pre($content) {
			$this->pre .= $content." \n";
		}

		public function select($vars) {
			$this->select .= "SELECT $vars \n";
			return $this;
		}

		public function from($table) {
			$this->from = $this->prependTabs(). "FROM $table \n";
			$this->last = 'from';
			return $this;
		}

		public function join($table, $on, $type = "INNER") {
			switch(strtolower($type)) {
				case 'inner': case 'left outer' : case 'left' : case 'right' : case 'right outer' : case 'full outer' :
				break;
				case 'l' :
					$type = "LEFT OUTER";
					break;
				case 'r' :
					$type = "RIGHT OUTER";
					break;
				case 'f' :
					$type = "FULL OUTER";
					break;
				default : $type = "INNER";
			} 
			$this->joins []= strtoupper($type)." JOIN $table ON $on\n";
			$this->last = 'joins';
			return $this;
		}

		/**
		 * The AND concatenator
		 */
		public function a($condition) {;
			if(is_array($this->{$this->last})) {
				$i = count($this->{$this->last}) - 1;
				$this->{$this->last}[$i] .= $this->prependTabs() . "AND $condition\n";
			}
			else if(is_string($this->{$this->last})) {
				$this->{$this->last} .= $this->prependTabs() . "AND $condition\n";
			}

			return $this;
		}

		/**
		 * The OR concatenator
		 */
		public function o($condition) {;
			if(is_array($this->{$this->last})) {
				$i = count($this->{$this->last}) - 1;
				$this->{$this->last}[$i] .= $this->prependTabs() . "OR $condition\n";
			}
			else if(is_string($this->{$this->last})) {
				$this->{$this->last} .= $this->prependTabs() . "OR $condition\n";
			}

			return $this;
		}

		public function where($condition) {
			if(is_string($condition)) {
				$this->where .= $this->prependTabs() . "WHERE $condition ";
			}
			else if(is_array($condition)) {
				$this->where .= $this->prependTabs() . "WHERE ";
				$this->where .= implode(" AND ", $condition)." ";
			}

			$this->last = 'where';

			$this->where .= "\n";

			return $this;
		}

		public function groupBy($column) {
			$this->groupBy = $this->prependTabs() . "GROUP BY $column \n";
			$this->last = 'groupBy';
			return $this;
		}

		public function orderBy($columns) {
			$this->orderBy = $this->prependTabs() . "ORDER BY $columns \n";
			$this->last = 'orderBy';
			return $this;
		}

		public function having($condition) {
			$this->having = $this->prependTabs() . "HAVING $columns \n";
			$this->last = 'having';
			return $this;
		}

		public function limit($offset = 0, $count = 100) {
			$this->limitSet = true;
			$this->limit .= $this->prependTabs() . "LIMIT $offset, $count";
			return $this;
		}

		public function getFrom() {
			//var_dump($this->subQuery);
			if($this->hasSubQuery) {
				return $this->subQuery->call();
			}

			return $this->from;
		}

		public function getJoins() {
			if(array_key_exists(0, $this->joins)) {
				$join = $this->prependTabs() . implode($this->prependTabs(), $this->joins);
				return $join;
			}

			return "";
		}

		private function outputTabs($levelsBack = 0) {
			$tabs = "";
			for($i = 0; $i < $this->level - $levelsBack; $i++) {
				$tabs .= "\t";
			}
			return $tabs;
		}

		private function prependTabs() {
			if($this->isSubquery) {
				return $this->outputTabs(). "  ";
			}

			return "";
		}

		public function prettyPrint() {
			$this->build();
			echo $this->query;
			return $this;
		}

		public function build() {
			$this->query = $this->pre . $this->select . $this->getFrom() . $this->getJoins() . $this->where . $this->groupBy . $this->having . $this->orderBy . $this->limit;
			if($this->isSubquery) {
				$this->query = $this->outputTabs(1). "  FROM \n" . $this->outputTabs() . "( " . $this->query . $this->outputTabs() .") AS " . $this->alias . " \n";
			}
			return $this;
		}

		public function call() {
			$this->build();
			return $this->query;
		}


		/// Fixes the iniablity to use 'and' and 'or' as a method name because it's a PHP reserved word

		public function __call($func, $args) {
			switch ($func) {
				case 'and':
					return $this->a($args[0]);
				break;
				case 'or':
					return $this->o($args[0]);
				break;
				default:
					trigger_error("Call to undefined method ".__CLASS__."::$func()", E_USER_ERROR);
				die ();
			}
		}

		public function __toString() {
			return "{Object : QueryBuilder} - query: " . $this->call();
		}

	}

}