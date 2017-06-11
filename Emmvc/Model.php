<?php


namespace Emmvc;





class Model
{
	use oInstan;

	/**
	 * mysql query builder
	 * @var Db
	 */
	public $db;
	
	public function __construct()
	{
		$this->db	= Em::$em->db;
	}
	
	
	
	
}