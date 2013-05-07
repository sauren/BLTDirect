<?php

require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class RowSet implements IteratorAggregate, Countable, ArrayAccess {
	private $rows = array();
	private $indices = array();
	private $groups = array();
	
	public function __construct($source, $connection=null) {
		if (is_string($source)) {
			$data = new DataQuery($source, $connection);
			$this->rows = $data->FetchAllObjects();
		} else {
			$this->rows = $source;
		}
	}
	
	public function rows() {
		return $this->rows;
	}
	
	// IteratorAggregate interface.
	public function getIterator() {
		return new ArrayIterator($this->rows);
	}
	
	// Countable interface.
	public function count() {
		return count($this->rows);
	}
	
	// ArrayAccess interface.
	public function offsetSet($offset, $value) {
		$this->rows[$offset] = $value;
	}
	public function offsetExists($offset) {
		return isset($this->rows[$offset]);
	}
	public function offsetUnset($offset) {
		unset($this->rows[$offset]);
	}
	public function offsetGet($offset) {
		return isset($this->rows[$offset]) ? $this->rows[$offset] : null;
	}

	
	// Get a row or row set by an indexed column.
	public function by($column, $value=null, $strict=false) {
		if (!isset($this->indices[$column])) {
			if ($strict) {
				trigger_error("Attempting to access non-indexed column", E_USER_ERROR);
			}
			$this->index($column);
		}
		
		$index = isset($this->indices[$column]) ? $this->indices[$column] : array();
		
		if (is_null($value)) {
			return $index;
		}
		
		return isset($index[$value]) ? $index[$value] : null;
	}
	
	// Lookup by group.
	public function byGroup($column, $value=null, $strict=false) {
		if (!isset($this->groups[$column])) {
			if ($strict) {
				trigger_error("Attempting to access non-indexed column", E_USER_ERROR);
			}
			$this->groupBy($column);
		}
		
		$group = isset($this->groups[$column]) ? $this->groups[$column] : array();
		
		if (is_null($value)) {
			return $group;
		}
		
		return isset($group[$value]) ? $group[$value] : array();
	}
	
	// Lookup a value in the first row, whatever that may be. Sometimes useful
	// if all rows have the same value.
	public function firstValue($field) {
		$rows = $this->rows;
		$first = array_shift($rows);
		return ($first && isset($first->$field)) ? $first->$field : null;
	}
	
	// Lookup a value in the first row of a particular index.
	public function firstValueBy($column, $value, $field) {
		$row = $this->by($column, $value);
		return ($row && isset($row->$field)) ? $row->$field : null;
	}
	
	// Exactly like byGroup but returns the first group no matter what the
	// index is. Useful for headers on table layouts.
	public function firstGroup($column, $value=null, $strict=false) {
		$groups = $this->byGroup($column, $value, $strict);
		$first = array_shift($groups);
		return $first ? $first : array();
	}
	
	// Create groups on the column(s) provided.
	public function groupBy(/*...*/) {
		$groups = func_get_args();
		foreach ($groups as $group) {
			$sets = array();
			foreach ($this->rows as &$row) {
				$sets[$row->$group][] = &$row;
			}
			
			foreach ($sets as $name => $set) {
				$this->groups[$group][$name] = new RowSet($set);
			}
		}
	}
	
	// Index the column(s) provided.
	public function index(/*...*/) {
		$columns = func_get_args();
		foreach ($columns as $column) {
			if (isset($this->indices[$column])) { continue; }
			$this->indices[$column] = array();
			
			foreach ($this->rows as &$row) {
				if (isset($this->indices[$column][$row->$column])) { continue; }
				$this->indices[$column][$row->$column] = &$row;
			}
		}
	}
}
