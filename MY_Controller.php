<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller {
	/**
	 * $data
	 * Data to be sent automatically to the view.
	 */
	protected $data;

	/**
	 * Our before and after filters. These allow us to avoid overriding the
	 * constructor.
	 */
	protected $before_filter = array();
	protected $after_filter = array();

	/**
	 * $formats
	 * An array of respondable formats. This maps the request type to a
	 * template and a template language, some examples included.
	 */
	private $formats = array(
		//'html' => 'custom/view/file.php',
		//'json' => 'custom/file.json'
	);

	/**
	 * Constructor
	 * Our custom init method to load in our template driver
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->driver('template');
	}

	/**
	 * Remap
	 * Overrides each request and allows us to automatically call the template
	 * parser after the controller's method has run.
	 */
	public function _remap($method)
	{
		// If the method doesn't exist bail out.
		if (!method_exists($this, $method)) {
			show_404();
		}

		// Run our :before_filter
		$this->run_filter('before');

		// Store the state of our controller prior to the method call
		$before = get_object_vars($this);

		// Call the method
		try {
			call_user_func_array(
				array($this, $method),
				array_slice($this->uri->rsegments, 2)
			);
		}

		// Catch any errors the method throws
		catch (Exception $e) {
			show_error($e->getMessage());
		}

		// Get the state of our controller after running the method
		$after = get_object_vars($this);

		// Figure out if anything changed. If it did then we'll want to add
		// those changes into the data array unless their name begins with an
		// underscore.
		$diff = array_diff(array_keys($after), array_keys($before));
		foreach ($diff as $key) {
			if (substr($key, 0, 1) == '_') {
				continue;
			}
			$this->data[$key] = $this->{$key};
		}

		// Run our :after_filter
		$this->run_filter('after');

		// Simplify the class variable
		$class = $this->router->class;

		// Strip any HTTP verbs out of the method variable so we can get to
		// the name of the view. Blog::get_posts would be a view of blog/posts
		$method = preg_replace(
			'/^(get|post|put|delete)_/',
			'',
			$this->router->method
		);

		// Get the format from the URI.
		$format = $this->uri->format();

		// Is a specific view defined for this format?
		if (isset($this->formats[$format])) {
			$view = $this->formats[$format];
		}
		
		// No defined view, assemble the default view/template
		else if($this->template->responds_to($format)) {
			$view = "{$class}/{$method}.{$format}";
		}

		// No suitable parser was defined/found for the format
		else {
			show_404();
		}

		// Check for redirects
		if (substr($view, 0, 1) == ':') {
			$this->load->helper('url');
			redirect(substr($view, 1));
		}

		// Setup some default data. We'll take the session flashdata, if it
		// exists and push it onto the data passed to the template
		if (isset($this->session) && !isset($this->data['flashdata'])) {
			$this->data['flashdata'] = new stdClass;
			foreach ($this->session->all_userdata() as $key => $value) {
				if (preg_match('/^flash:old:(.*)$/', $key, $match)) {
					$this->data['flashdata']->{$match[1]} = $value;
				}
			}
		}

		// Finally, render out the template; assuming the developer still
		// wants us to.
		$this->template->render($view, $this->data);

		// Get the parser defined in the URL. This could be different from the
		// parser used to generate the page. For example a JSON request could
		// be driven by a `twig` template. In this case we'll want to pull the
		// content type from `json` even though the template engine is `twig`.
		$parser = $this->uri->format();
		$content_type = $this->template->{$parser}->content_type;
		$this->output->set_content_type($content_type);
	}

	/**
	 * Run the before and after filters.
	 */
	private function run_filter($who) {
		$filter = $this->{"{$who}_filter"};

		if (is_string($filter)) {
			$filter = array($filter);
		}

		if (method_exists($this, "{$who}_filter")) {
			$filter[] = "{$who}_filter";
		}

		foreach ($filter as $method) {
			call_user_func_array(
				array($this, $method),
				array_slice($this->uri->rsegments, 2)
			);
		}
	}

	/**
	 * Responds To
	 * Set the templates to use in the response per format
	 */
	public function responds_to($formats, $empty_defaults=FALSE)
	{
		if ($empty_defaults) {
			$this->formats = array();
		}

		foreach ($formats as $key => $value)
		{
			if (is_numeric($key))
			{
				$this->formats[$value] = FALSE;
			}
			else
			{
				$this->formats[$key] = $value;
			}
		}
	}
}