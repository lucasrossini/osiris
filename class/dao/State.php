<?php
	namespace DAO;
	
	/**
	 * Classe para registro de estado.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 17/10/2012
	*/
	
	class State extends \Database\DatabaseObject{
		const TABLE_NAME = 'sys_state';
		
		protected $name;
		protected $acronym;
	}
?>