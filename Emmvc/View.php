<?php
/**
 * 
 */


namespace Emmvc;



class View
{

	public static $directory = [];

	public static $ext = '.php';

	private $__view = NULL;
	
	public $data;

	/**
	 * Returns a new view object for the given view.
	 *
	 * @param string $file the view file to load
	 * @param string $module name (blank for current theme)
	 */
	public function __construct($file)
	{
		$this->__view = $file;
	}


	/**
	 * Set an array of values
	 *
	 * @param array $array of values
	 */
	public function set($array)
	{
		$this->data	= $array;
	}


	/**
	 * Return the view's HTML
	 *
	 * @return string
	 */
	public function __toString()
	{
		try {
			ob_start();
			extract((array) $this->data);
			foreach (static::$directory as $p)
			{
				if (file_exists($p = $p . $this->__view . static::$ext))
				require $p;
			}
			return ob_get_clean();
		}
		catch(\Exception $e)
		{
			return '';
		}
	}

}


