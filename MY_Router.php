<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Router extends CI_Router {
	/**
	 * Set Request
	 * Our custom method to prefix HTTP verbs onto our method names. This is
	 * copied, almost letter for letter, from the Router.php:_set_request()
	 * method as of 3.0-dev
	 */
	public function _set_request($segments) {
		$verb = strtolower($_SERVER['REQUEST_METHOD']).'_';
		$segments = $this->_validate_request($segments);

		if (count($segments) == 0)
		{
			return $this->_set_default_controller();
		}

		$this->set_class($segments[0]);
		
		if (isset($segments[1]))
		{
			// A standard method request
			$this->set_method($verb.$segments[1]);
		}
		else
		{
			// This lets the "routed" segment array identify that the default
			// index method is being used.
			$segments[1] = $verb.'index';
			$this->set_method($verb.'index');
		}

		// Update our "routed" segment array to contain the segments.
		// Note: If there is no custom routing, this array will be
		// identical to $this->uri->segments
		$this->uri->rsegments = $segments;
	}
}