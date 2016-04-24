<?php  


namespace Emmvc;



class Benchmark {

	/**
	 * List of all benchmark markers and when they were added
	 *
	 * @var array
	 */
	public $marker = array();

	// --------------------------------------------------------------------

	/**
	 * Set a benchmark marker
	 *
	 * Multiple calls to this function can be made so that several
	 * execution points can be timed
	 *
	 * @access	public
	 * @param	string	$name	name of the marker
	 * @return	void
	 */
	function start($name)
	{
		$this->marker[$name]['s'] = microtime();
	}
	
	public function end($name)
	{
		$this->marker[$name]['e'] = microtime();
	}

	// --------------------------------------------------------------------

	/**
	 * Calculates the time difference between two marked points.
	 *
	 * If the first parameter is empty this function instead returns the
	 * {elapsed_time} pseudo-variable. This permits the full system
	 * execution time to be shown in a template. The output class will
	 * swap the real value for this variable.
	 *
	 * @access	public
	 * @param	string  $name name of the marker
	 * @param	integer	the number of decimal places
	 * @return	mixed
	 */
	function elapsed_time($name, $decimals = 4)
	{
		if ( ! isset($this->marker[$name]['s']))
		{
			return '';
		}

		if ( ! isset($this->marker[$name]['e']))
		{
			$this->marker[$name]['e'] = microtime();
		}

		list($sm, $ss) = explode(' ', $this->marker[$name]['s']);
		list($em, $es) = explode(' ', $this->marker[$name]['e']);

		return number_format(($em + $es) - ($sm + $ss), $decimals);
	}

	// --------------------------------------------------------------------


}


