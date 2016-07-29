<?php
	namespace DAO;
	
	/**
	 * Classe para registro de país.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 17/10/2012
	*/
	
	class Country extends \Database\DatabaseObject{
		const TABLE_NAME = 'sys_country';
		
		//Dados da API
		public static $api_data = array(
			'name' => 'countries',
			'methods' => array('list', 'get', 'search'),
			'xml' => array('group' => 'countries', 'item' => 'country')
		);
		
		protected $name;
		protected $iso;
		protected $iso3;
		protected $code;
	}
?>