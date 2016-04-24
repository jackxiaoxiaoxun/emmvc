<?php


namespace Emmvc;





class Model
{
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