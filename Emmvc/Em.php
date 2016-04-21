<?php


namespace Emmvc;



class Em
{
	public static $em;
	public $config;
	
	public function __construct($config)
	{
		Config::$configPath		= $config['configDir'];
		View::$directory[]		= $config['themes'];
	}
	
	public static function run($config)
	{
		self::$em	= new self($config);
		self::$em->bootstrap();
	}
	
	public function bootstrap()
	{
		$this->config	= new Config();
		$this->uri		= new URI(Config::get('uri'));
		$this->router	= new Router($this->uri, Config::get('routes'));

		$this->router->_set_routing();

	}
	
	
	
	
	
	
	
	
	
}