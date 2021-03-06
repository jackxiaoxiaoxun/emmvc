<?php


namespace Emmvc;





class Router
{

	/**
	 * Config class
	 *
	 * @var object
	 * @access public
	 */
	public $config;
	/**
	 * List of routes
	 *
	 * @var array
	 * @access public
	 */
	public $routes			= array();
	/**
	 * List of error routes
	 *
	 * @var array
	 * @access public
	 */
	public $error_routes	= array();
	/**
	 * Current class name
	 *
	 * @var string
	 * @access public
	 */
	public $class			= '';
	/**
	 * Current method name
	 *
	 * @var string
	 * @access public
	 */
	public $method			= 'index';
	/**
	 * Sub-directory that contains the requested controller class
	 *
	 * @var string
	 * @access public
	 */
	public $ns		= '';
	/**
	 * Default controller (and method if specific)
	 *
	 * @var string
	 * @access public
	 */
	public $default_controller;
	/**
	 * 
	 * @var URI
	 */
	private $uri;

	/**
	 * Constructor
	 *
	 * Runs the route mapping function.
	 */
	function __construct($uri, $routes)
	{
		$this->uri		= $uri;
		$this->config	= $uri->config;
		$this->routes	= $routes;
		
		$this->set_ns($routes['default_NS']);
	}

	// --------------------------------------------------------------------

	/**
	 * Set the route mapping
	 *
	 * This function determines what should be served based on the URI request,
	 * as well as any "routes" that have been set in the routing config file.
	 *
	 * @access	private
	 * @return	void
	 */
	function _set_routing()
	{
		// Are query strings enabled in the config file?  Normally CI doesn't utilize query strings
		// since URI segments are more search-engine friendly, but they can optionally be used.
		// If this feature is enabled, we will gather the directory/class/method a little differently
		$segments = array();
		if ($this->config['enable_query_strings'] === TRUE AND isset($_GET[$this->config['controller_trigger'] ]))
		{
			if (isset($_GET[$this->config['ns_trigger'] ])
					and isset( $this->routes [ trim($this->uri->_filter_uri($_GET[$this->config['ns_trigger'] ])) ] ['ns'] ))
			{
				$this->set_ns($this->routes [ trim($this->uri->_filter_uri($_GET[$this->config['ns_trigger'] ])) ] ['ns'] );
			}

			if (isset($_GET[$this->config['controller_trigger'] ]))
			{
				$this->set_class( $seg = trim($this->uri->_filter_uri($_GET[$this->config['controller_trigger'] ])));
				$segments[] = $seg;
			}

			if (isset($_GET[$this->config['function_trigger'] ]))
			{
				$this->set_method(trim($this->uri->_filter_uri($_GET[$this->config['function_trigger'] ])));
				$segments[] = $this->fetch_method();
			}
		}

		
		// Set the default controller so we can display it in the event
		// the URI doesn't correlated to a valid controller.
		$this->default_controller = ( ! isset($this->routes['default_controller']) OR $this->routes['default_controller'] == '') ? FALSE : strtolower($this->routes['default_controller']);

		// Were there any query string segments?  If so, we'll validate them and bail out since we're done.

		if (count($segments) > 0)
		{
			return $this->_validate_request($segments);
		}

		// Fetch the complete URI string
		$this->uri->_fetch_uri_string();

		// Is there a URI string? If not, the default controller specified in the "routes" file will be shown.
		if ($this->uri->uri_string == '')
		{
			return $this->_set_default_controller();
		}

		// Do we need to remove the URL suffix?
		$this->uri->_remove_url_suffix();

		// Compile the segments into an array
		$this->uri->_explode_segments();

		// Parse any custom routing that may exist
		$this->_parse_routes();

		// Re-index the segment array so that it starts with 1 rather than 0
		$this->uri->_reindex_segments();
	}

	// --------------------------------------------------------------------

	/**
	 * Set the default controller
	 *
	 * @access	private
	 * @return	void
	 */
	function _set_default_controller()
	{
		if ($this->default_controller === FALSE)
		{
			throw new \ErrorException('"Unable to determine what should be displayed. A default route has not been specified in the routing file."');
		}
		// Is the method being specified?
		if (strpos($this->default_controller, '/') !== FALSE)
		{
			$x = explode('/', $this->default_controller);

			$this->set_class($x[0]);
			$this->set_method($x[1]);
			$this->_set_request($x);
		}
		else
		{
			$this->set_class($this->default_controller);
			$this->set_method('index');
			$this->_set_request(array($this->default_controller, 'index'));
		}

		// re-index the routed segments array so it starts with 1 rather than 0
		$this->uri->_reindex_segments();

	}

	// --------------------------------------------------------------------

	/**
	 * Set the Route
	 *
	 * This function takes an array of URI segments as
	 * input, and sets the current class/method
	 *
	 * @access	private
	 * @param	array
	 * @param	bool
	 * @return	void
	 */
	function _set_request($segments = array())
	{
		$segments = $this->_validate_request($segments);

		if (count($segments) == 0)
		{
			return $this->_set_default_controller();
		}

		$this->set_class($segments[0]);

		if (isset($segments[1]))
		{
			// A standard method request
			$this->set_method($segments[1]);
		}
		else
		{
			// This lets the "routed" segment array identify that the default
			// index method is being used.
			$segments[1] = 'index';
		}

		// Update our "routed" segment array to contain the segments.
		// Note: If there is no custom routing, this array will be
		// identical to $this->uri->segments
		$this->uri->rsegments = $segments;
	}

	// --------------------------------------------------------------------

	/**
	 * Validates the supplied segments.  Attempts to determine the path to
	 * the controller.
	 *
	 * @access	private
	 * @param	array
	 * @return	array
	 */
	function _validate_request($segments)
	{
		if (count($segments) == 0)
		{
			return $segments;
		}

		$class		= $this->ns . $segments[0] . 'Controller';
		if (Em::$em->autoLoader->loadClass($class))
		{
			return $segments;
		}

		// If we've gotten this far it means that the URI does not correlate to a valid
		// controller class.  We will now see if there is an override
		if ( ! empty($this->routes['404_override']))
		{
			http_response_code(404);
			$x = explode('/', $this->routes['404_override']);

			$this->set_class($x[0]);
			$this->set_method(isset($x[1]) ? $x[1] : 'index');

			return $x;
		}


		// Nothing else to do at this point but show a 404
		Common::show_404($segments[0]);
	}

	// --------------------------------------------------------------------

	/**
	 *  Parse Routes
	 *
	 * This function matches any routes that may exist in
	 * the config/routes.php file against the URI to
	 * determine if the class/method need to be remapped.
	 *
	 * @access	private
	 * @return	void
	 */
	function _parse_routes()
	{
		// Turn the segment array into a URI string
		$uri = implode('/', $this->uri->segments);

		if (isset($this->routes [ $this->uri->segments[0] ]  ['ns']))
		{
				$this->ns    = $this->routes [ $this->uri->segments[0] ] ['ns'];

			if (isset( $this->routes [ $this->uri->segments[0] ] ['class'] ))
				return $this->_set_request(explode('/',  $this->routes [ $this->uri->segments[0] ] ['class'] ));

				array_shift( $this->uri->segments );
			return 	$this->_set_request($this->uri->segments);
		}
		

		// Loop through the route array looking for wild-cards
		foreach ($this->routes as $key => $val)
		{
			$len		= strlen($key);
			if (substr($uri, 0, $len) == $key)
			{
				if (isset($val['ns']))
					$this->ns	= $val['ns'];

				if (isset($val['class']))
					return $this->_set_request(explode('/', $val['class']));
			}
			
			
			// Convert wild-cards to RegEx
			$key = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $key));

			// Does the RegEx match?
			if (preg_match('#^'.$key.'$#', $uri))
			{
				// Do we have a back-reference?
				if (strpos($val['class'], '$') !== FALSE AND strpos($key, '(') !== FALSE)
				{
					$val['class'] = preg_replacuserse('#^'.$key.'$#', $val['class'], $uri);
				}

				if (isset($val['ns']))
					$this->ns	= $val['ns'];
				return $this->_set_request(explode('/', $val['class']));
			}
		}
		
		// If we got this far it means we didn't encounter a
		// matching route so we'll set the site default route
		$this->_set_request($this->uri->segments);
	}

	// --------------------------------------------------------------------

	/**
	 * Set the class name
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_class($class)
	{
		$this->class = $this->ns . str_replace(array('/', '.'), '', $class) . 'Controller';
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch the current class
	 *
	 * @access	public
	 * @return	string
	 */
	function fetch_class()
	{
		return $this->class;
	}

	// --------------------------------------------------------------------

	/**
	 *  Set the method name
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_method($method)
	{
		$this->method = $method;
	}

	// --------------------------------------------------------------------

	/**
	 *  Fetch the current method
	 *
	 * @access	public
	 * @return	string
	 */
	function fetch_method()
	{
		if ($this->method == $this->fetch_class())
		{
			return 'index';
		}

		return $this->method;
	}

	// --------------------------------------------------------------------

	/**
	 *  Set the namespace
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_ns($ns)
	{
		$this->ns = $ns;
	}

	// --------------------------------------------------------------------

	/**
	 *  Fetch namespace
	 *
	 * @access	public
	 * @return	string
	 */
	function fetch_ns()
	{
		return $this->ns;
	}



}

