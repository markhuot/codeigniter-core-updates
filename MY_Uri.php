<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Uri extends CI_Uri {
	/**
	 * $format
	 * The default format for URIs without a suffix. For example blog/posts may
	 * be defined as a HTML format or a JSON format.
	 */
	private $format = 'html';

	/**
	 * $format_regex
	 * The regex to parse the uri for the format
	 */
	private $format_regex = '/^(.*)\.(\w{3,})$/';

	public function __construct() {
		parent::__construct();
		$this->config->load('uri');
	}

	/**
	 * Get Format
	 */
	public function format() {
		return $this->format;
	}

	/**
	 * Remove URL Suffix overrides the existing CI method to remove ANY format,
	 * not just the single format defined in the config.
	 */
	public function _remove_url_suffix() {
		$this->format = $this->format_from_uri($this->uri_string);
		$this->uri_string = $this->remove_format_from_uri($this->uri_string);
	}

	public function format_from_uri($uri) {
		preg_match(
			$this->format_regex,
			$uri,
			$parts
		);

		if (@$parts[2]) {
			return @$parts[2];
		}

		if ($this->config->item('default_format')) {
			return $this->config->item('default_format');
		}

		return $this->format;
	}

	private function remove_format_from_uri($uri) {
		preg_match(
			$this->format_regex,
			$uri,
			$parts
		);
		return @$parts[1]?:$uri;
	}
}