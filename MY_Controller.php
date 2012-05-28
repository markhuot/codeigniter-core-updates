<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

	/**
	 * Headers to set when calling the constructor
	 */
	protected $content_type = 'text/html';
	protected $cache_control = 'no-cache';

	/**
	 * The Layout and View to load after the controller function runs.
	 */
	protected $layout = 'application/layout';
	protected $view;

	/**
	 * Our before and after filters. These allow us to avoid overriding the
	 * constructor.
	 */
	protected $before_filter = array();
	protected $after_filter = array();

	/**
	 * Remap
	 * Overrides each request and allows us to automatically call the view
	 * after the controller's method has run.
	 */
	public function _remap($method, $params=array())
	{
		// Determine the default view
		$this->view = strtolower(get_class($this).'/'.$method);

		// Get the request verb
		$verb = strtolower($_SERVER['REQUEST_METHOD']);

		// Check if the type is defined via URL suffix
		$suffix = FALSE;
		$match = '/\.([^.]{3,})$/';
		if (preg_match($match, $method, $matches)) {
			$this->load->helper('file');
			$this->content_type = get_mime_by_extension($method);
			$method = preg_replace($match, '', $method);
		}

		// If the method doesn't exist bail out.
		if (method_exists($this, "{$verb}_{$method}")) {
			$method = "{$verb}_{$method}";
		}
		else if (method_exists($this, $method)) {
			$method = $method;
		}
		else {
			show_404();
		}

		// Get the state of the controller before running any methods
		$before = get_object_vars($this);

		// Call the method
		try {
			$this->run_filter('before', $params);
			$return = call_user_func_array(array($this, $method), $params);
			$this->run_filter('after', $params);
		}

		// Catch any errors the method throws
		catch (Exception $e) {
			if ($this->content_type == 'application/json') {
				$this->output->set_content_type($this->content_type);
				$err = array();
				$err['error'] = true;
				$err['message'] = $e->getMessage();
				$err['code'] = $e->getCode();
				if (ENVIRONMENT == 'development') {
					$err['file'] = $e->getFile();
					$err['line'] = $e->getLine();
				}
				$this->output->set_output(json_encode($err));
				return;
			}
			else {
				show_error($e->getMessage());
			}
		}

		// Get the state of our controller after running the method
		$after = get_object_vars($this);

		// Figure out if anything changed. If it did then we'll want to make
		// those changes available to the view
		$vars = array_diff_key($after, $before);

		// Set the headers now that the controller has had a chance to
		// modify them.
		if ($this->content_type) {
			$this->output->set_content_type($this->content_type);
		}
		if ($this->cache_control) {
			header("Cache-control: {$this->cache_control}");
			header("Edge-control: {$this->cache_control}");
		}

		// If we have return data from the controller method stop here and just
		// render that out.
		if ($return) {
			if ($this->content_type == 'application/json') {
				$return = json_encode($return);
			}
			$this->output->set_output($return);
			return;
		}

		// Get the state of the loader object before loading our view
		$before = get_object_vars($this->load);

		// Render the view out to a string.
		if ($this->view) {
			$output = $this->load->view($this->view, $vars, TRUE);
		}

		// Get the state of the loader after running the view. This allows
		// class variables set in a view to bubble up to the layout
		$after = get_object_vars($this->load);
		$view_vars = array_diff_key($after, $before);

		// Merge our vars for inclusion into the layout
		$vars = array_merge($vars, $view_vars, array('yield'=>$output));

		// Render the layout
		if ($this->layout) {
			$output = $this->load->view('application/layout', $vars, TRUE);
		}

		$this->output->set_output($output);
	}

	/**
	 * Run the before and after filters.
	 */
	private function run_filter($who, $params=array()) {
		$filter = $this->{"{$who}_filter"};

		if (is_string($filter)) {
			$filter = array($filter);
		}

		if (method_exists($this, "{$who}_filter")) {
			$filter[] = "{$who}_filter";
		}

		foreach ($filter as $method) {
			call_user_func_array(array($this, $method), $params);
		}
	}
}