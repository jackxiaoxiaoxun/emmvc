<?php

namespace Emmvc;



class Hooks
{

	/**
	 * Determines wether hooks are enabled
	 *
	 * @var bool
	 */
	public  $enabled		= true;
	/**
	 * List of all hooks set in config/hooks.php
	 *
	 * @var array
	 */
	public  $hooks			= array();
	/**
	 * Determines wether hook is in progress, used to prevent infinte loops
	 *
	 * @var array
	 */
	public  $in_progress	= [];

	/**
	 * this
	 * @var Hooks
	 */
	private static $instance;
	/**
	 * 事件类型　根据不同的事件类型
	 * 调用不同的事件配置文件
	 * @var string
	 */
	public $type;
	
	/**
	 * 触发事件时获取的资源
	 * @var \stdClass
	 */
	public $assets;

	/**
	 * Constructor
	 *
	 */
	function __construct()
	{

	}

	/**
	 * 运行事件
	 * @param string $which 事件名称
	 * @param string $type  事件类型 '':pc站 m:手机站
	 * @return Hooks
	 */
	public static function  event($which, $assets = '', $type = '')
	{
		if (empty(self::$instance))
		{
			self::$instance		= new static();
		}

		self::$instance->type	= $type;
		self::$instance->assets	= $assets;
		self::$instance->position($which);
		self::$instance->type	= '';
		self::$instance->assets	= '';

		return self::$instance;
	}

	/**
	 *	new static
	 * @access	public
	 * @return	Hooks
	 */
	public static function instance()
	{
		if (empty(self::$instance))
		{
			self::$instance		= new static;
		}
		return self::$instance;
	}

	// --------------------------------------------------------------------
	
	public function position($which)
	{
		$hooks 	= 'hooks' . $this->type;
		foreach ( Em::$em->config->$hooks  as $hook)
		{
			$this->hooks	=& $hook;
			$this->_call_hook($which);
		}
	}

	/**
	 * Call Hook
	 *
	 * Calls a particular hook
	 *
	 * @access	private
	 * @param	string	the hook name
	 * @return	mixed
	 */
	function _call_hook($which = '')
	{
		if ( ! $this->enabled OR ! isset($this->hooks[$which]))
		{
			return FALSE;
		}

		if (isset($this->hooks[$which][0]) AND is_array($this->hooks[$which][0]))
		{
			foreach ($this->hooks[$which] as $val)
			{
				$this->_run_hook($val, $which);
			}
		}
		else
		{
			$this->_run_hook($this->hooks[$which], $which);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Run Hook
	 *
	 * Runs a particular hook
	 *
	 * @access	private
	 * @param	array	the hook details
	 * @return	bool
	 */
	function _run_hook($data, $which = 0)
	{
		if ( ! is_array($data))
		{
			return FALSE;
		}

		
		// -----------------------------------
		// Safety - Prevents run-away loops
		// -----------------------------------

		// If the script being called happens to have the same
		// hook call within it a loop can happen

		if (isset($this->in_progress[$which]))
		{
			return;
		}

		// -----------------------------------
		// Set class/function name
		// -----------------------------------

		$class		= FALSE;
		$function	= FALSE;
		$params		= [];

		if (isset($data['class']) AND $data['class'] != '')
		{
			$class = $data['class'];
		}

		if (isset($data['function']))
		{
			$function = $data['function'];
		}

		if (isset($data['params']))
		{
			$params = $data['params'];
		}

		if ($class === FALSE AND $function === FALSE)
		{
			return FALSE;
		}
		// -----------------------------------
		// Set the in_progress flag
		// -----------------------------------

		$this->in_progress[$which] = TRUE;

		// -----------------------------------
		// Call the requested class and/or function
		// -----------------------------------
		if ($class !== FALSE)
		{
			$class	= new $class;
			call_user_func_array([$class, $function], $params);
		}

		
		unset($this->in_progress[$which]);
		return TRUE;
	}

}


