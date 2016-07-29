<?php
	namespace DAO\Ecommerce;
	
	/**
	 * Classe para registro de cliente.
	 * 
	 * @package Osiris/E-Commerce
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 27/02/2014
	*/
	
	class Client extends \Database\DatabaseObject{
		const TABLE_NAME = 'ecom_client';
		
		protected $name;
		protected $email;
		protected $cpf;
		protected $phone;
		protected $signup_date;
		protected $signup_time;
		
		/**
		 * Carrega as buscas mais realizadas pelo cliente.
		 * 
		 * @param int $count Quantidade de buscas.
		 * @return array Vetor multidimensional de buscas realizadas, com os índices 'query', que indica os termos da busca; e 'count', que indica quantas vezes a busca foi realizada.
		 */
		public function get_top_searches($count = 10){
			global $db;
			$top_searches = array();
			
			$db->query('SELECT DISTINCT s.query, (SELECT COUNT(*) FROM ecom_search s2 WHERE s2.query = s.query) AS total FROM ecom_search s WHERE s.client_id = '.$this->id.' ORDER BY total DESC LIMIT 0,'.(int)$count);
			$searches = $db->result();
			
			if(!(sizeof($searches) === 1 && empty(reset($searches)->query))){
				foreach($searches as $search)
					$top_searches[] = array('query' => $search->query, 'count' => $search->total);
			}
			
			return $top_searches;
		}
	}
?>