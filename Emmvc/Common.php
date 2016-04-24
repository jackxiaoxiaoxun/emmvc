<?php

namespace Emmvc;

class Common
{
	public static function show_404($name)
	{
		http_response_code(404);
		throw new \ErrorException( $name . '页面没找到');
	}
}