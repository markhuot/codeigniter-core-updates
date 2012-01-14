<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller {
	/**
	 * $data
	 * Data to be sent automatically to the view.
	 */
	protected $data;

	/**
	 * $default_format
	 * Sets the default format to be used when no suffix is included in the
	 * URI.
	 */
	protected $default_format = 'html';

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
		$this->default_format = $this->config->item('default_format')?:
			$this->default_format;
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

		// Call the method
		call_user_func_array(
			array($this, $method),
			array_slice($this->uri->rsegments, 2)
		);

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
		$format = $this->uri->format($this->default_format);

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

		// Finally, render out the template; assuming the developer still
		// wants us to.
		$this->template->render($view, $this->data);

		// Get the parser defined in the URL. This could be different from the
		// parser used to generate the page. For example a JSON request could
		// be driven by a `twig` template. In this case we'll want to pull the
		// content type from `json` even though the template engine is `twig`.
		$parser = $this->uri->format($this->default_format);
		$content_type = $this->template->{$parser}->content_type;
		$this->output->set_content_type($content_type);
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