<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Model extends MY_Record {

	protected $_table = FALSE;
	protected $_primary_key = 'id';
	protected $_default_scope = array(
		// 'where' => array(),
		// 'group_by' => 'title',
		// 'order_by' => 'id ASC',
		// 'limit' => 100
	);

	public function __construct($attributes=array()) {
		parent::__construct($attributes);
		$this->load->helper('inflector');
	}

	public function table() {
		if (!$this->_table) {
			$this->_table = get_class($this);
			$this->_table = preg_replace('/_model$/', '', $this->_table);
			$this->_table = strtolower($this->_table);
			$this->_table = plural($this->_table);
		}

		return $this->_table;
	}

	public function primary_key() {
		return $this->_primary_key;
	}

	public function count() {
		return $this->db->count_all($this->table());
	}

	public function get($opts=array()) {
		if (is_numeric($opts)) {
			return $this->first(array(
				'where' => array("{$this->table}.{$this->primary_key}" => $opts)
			));
		}

		$opts = array_merge_recursive_overwrite($this->_default_scope, $opts);

		if (isset($opts['select'])) {
			foreach ($opts['select'] as $field => $name) {
				if (is_numeric($field)) {
					$this->db->select($name);
				} else if (preg_match('/[^a-z0-9_-]/i', $field)) {
					$this->db->select("{$field} as {$name}", FALSE);
				} else {
					$this->db->select("{$field} as {$name}");
				}
			}
		}

		if (isset($opts['join'])) {
			foreach ($opts['join'] as $join) {
				$this->db->join($join[0], $join[1], @$join[2]);
			}
		}

		if (isset($opts['where'])) {
			$this->db->where($opts['where']);
		}

		if (isset($opts['group_by'])) {
			$this->db->group_by($opts['group_by']);
		}

		if (isset($opts['order_by'])) {
			$this->db->order_by($opts['order_by']);
		}

		if (isset($opts['limit']) || isset($opts['offset'])) {
			$this->db->limit(@$opts['limit'], @$opts['offset']);
		}

		$class = get_class($this);
		$result = $this->db->get($this->table())->result();
		foreach ($result as &$record) {
			$record = new $class($record);
		}
		return $result;
	}

	public function first($opts=array()) {
		if ($result = $this->get($opts)) {
			return @$result[0];
		}

		throw new Exception("The {$this} could not be found.");
	}

	public function all() {
		return $this->get();
	}

	public function __toString() {
		return singular($this->table());
	}

	public function __call($method, $args) {
		if (preg_match('/get_by_(.*)/', $method, $matches)) {
			return $this->first(array(
				'where' => array(
					"{$this->table()}.{$matches[1]}" => $args[0]
				)
			));
		}

		return parent::__call($method, $args);
	}

	public function __get($key) {
		if (method_exists($this, $key)) {
			return call_user_func(array($this, $key));
		}
		
		return parent::__get($key);
	}

	public function includes($what) {
		return $this;
	}

	public function joins($what) {
		return $this;
	}

}

class MY_Record extends CI_Model {

	public function __construct($attributes=array()) {
		parent::__construct();
		foreach ($attributes as $key => $value) {
			$this->{$key} = $value;
		}
	}

	public function __get($key) {
		return parent::__get($key);
	}

	public function id() {
		return $this->{$this->primary_key};
	}

}

if (!function_exists('array_merge_recursive_overwrite')) {
	function array_merge_recursive_overwrite() {

		if (func_num_args() < 2) {
			trigger_error(__FUNCTION__ .' needs two or more array arguments', E_USER_WARNING);
			return;
		}
		$arrays = func_get_args();
		$merged = array();
		while ($arrays) {
			$array = array_shift($arrays);
			if (!is_array($array)) {
				trigger_error(__FUNCTION__ .' encountered a non array argument', E_USER_WARNING);
				return;
			}
			if (!$array)
				continue;
			foreach ($array as $key => $value)
				if (is_string($key))
					if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key]))
						$merged[$key] = call_user_func(__FUNCTION__, $merged[$key], $value);
					else
						$merged[$key] = $value;
				else
					$merged[] = $value;
		}
		return $merged;
	}
}