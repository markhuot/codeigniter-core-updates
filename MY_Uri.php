<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Uri extends CI_Uri {
	/**
	 * $format
	 * The default format for URIs without a suffix. For example blog/posts may
	 * be defined as a HTML format or a JSON format.
	 */
	private $format = FALSE;

	/**
	 * $format_regex
	 * The regex to parse the uri for the format
	 */
	private $format_regex = '/^(.*)\.(\w{3,})$/';

	/**
	 * Get Format
	 */
	public function format($fallback=FALSE) {
		if (!$this->format && $fallback) {
			return $fallback;
		}

		return $this->format;
	}

	public function format_from_uri($uri) {
		preg_match(
			$this->format_regex,
			$uri,
			$parts
		);
		return @$parts[2];
	}

	public function remove_format_from_uri($uri) {
		preg_match(
			$this->format_regex,
			$uri,
			$parts
		);
		return @$parts[1]?:$uri;
	}

	/**
	 * Remove URL Suffix
	 */
	public function _remove_url_suffix() {
		$this->format = $this->format_from_uri($this->uri_string);
		$this->uri_string = $this->remove_format_from_uri($this->uri_string);
	}
}