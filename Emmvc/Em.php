<?php


namespace Emmvc;



class Em extends Service
{
	public static $em;
	public $config;
	public $uri;
	public $router;
	public $controller;
	/**
	 * @var \Composer\Autoload\ClassLoader
	 */
	public $autoLoader;
	
	public function __construct($config)
	{
		Config::$configPath		= $config['configDir'];
		View::$directory[]		= $config['themes'];
		$this->autoLoader		= $config['autoLoader'];

		parent::__construct([]);
	}
	
	public static function run($config)
	{
		self::$em	= new self($config);
		self::$em->bootstrap();
		self::$em->createController();
	}
	
	public function bootstrap()
	{
		$this->config	= new Config();
		$this->uri		= new URI(Config::get('uri'));
		$this->router	= new Router($this->uri, Config::get('routes'));
		$this->router->_set_routing();
	}
	
	
	public function createController()
	{
		$class	= $this->router->fetch_class();

		$this->controller	= new $class;
		
		$run		= strtolower($_SERVER['REQUEST_METHOD']) .
					$this->router->fetch_method();
					
		if (method_exists($this->controller, $run))
		{
			$this->controller->$run();
		} else
		{
			Common::show_404('页面不存在');
		}
	}
	
	
	
	
	
	
	
}