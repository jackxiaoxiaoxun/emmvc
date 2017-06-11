<?php



namespace Emmvc;


trait oInstan
{
	private static  $_s;
	
	public static function inst()
	{
		$class = $k = get_called_class();
		if(empty(self::$_s[$k]))
			self::$_s[$k]	= new $class;

		return self::$_s[$k];
	}
	
}
