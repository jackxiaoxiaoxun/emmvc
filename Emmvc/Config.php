<?php


namespace Emmvc;



/**
 * 
 * 获取配置文件
 *
 */

class Config
{

	/**
	 * 配置文件夹路径
	 * @var string
	 */
	public $configPath = '';
	
	/**
	 * 获取配置数组
	 * @param string $name
	 */
	public function __get($name)
	{
		$this->$name	= require $this->configPath . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR .'config.php';

		return $this->$name;
	}
	
	/**
	 * 
	 * @param string $path
	 * @param number $dict 0:索引数组 1:关联数组
	 * @return array
	 */
	public static function getConfig($path, $dict = 0)
	{
		$item	= glob($path);
		$config	= [];
		foreach ($item as $i)
		{
			if ($dict == 1 )
				$config[ pathinfo($i)['filename'] ]	= require $i;
			else
				$config[]	= require $i;
		}
		
		unset($i, $item);
		return $config;
	}
	
	
}


