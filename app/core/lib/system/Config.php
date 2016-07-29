<?php
	namespace System;
	
	/**
	 * Classe para manipular as configurações do sistema.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 30/07/2013
	*/
	
	class Config{
		const ID = 1;
		const TABLE_PREFIX = 'conf_';
		
		private $values = array();
		
		/**
		 * Instancia um objeto de configuração.
		 * 
		 * @param string $table Nome da tabela de configuração.
		 */
		public function __construct($table = 'general'){
			global $db;
			
			$db->query('SELECT * FROM '.self::TABLE_PREFIX.$table.' WHERE id = '.self::ID);
			$values = $db->result(0);
			
			foreach($values as $field => $value)
				$this->values[$field] = $value;
		}
		
		/**
		 * Retorna o valor de um campo da tabela de configurações.
		 * 
		 * @param string $field Campo da tabela do banco de dados onde está localizado o registro de configuração do sistema.
		 * @return string|null Valor do campo.
		 */
		public function get($field){
			if(array_key_exists($field, $this->values))
				return $this->values[$field];
			
			return null;
		}
	}
?>