<?php
	namespace System;
	
	/**
	 * Classe que provê uma API para as classes DAO.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 18/10/2013
	*/
	
	abstract class API{
		//Erros
		const ERROR_INVALID_PARAMS = 1;
		const ERROR_INVALID_TYPE = 2;
		const ERROR_INVALID_METHOD = 3;
		const ERROR_INVALID_CALL = 4;
		const ERROR_INVALID_RECORD = 5;
		
		/**
		 * Define os métodos da API da classe.
		 * 
		 * @param string $method Nome do método chamado, que pode ser 'list', 'get', 'search' ou 'delete'.
		 * @param string $type Tipo de retorno, que pode ser 'json' ou 'xml'.
		 */
		public static function call($method, $type = 'json'){
			global $sys_control;
			
			$called_class = get_called_class();
			$type = strtolower($type);
			$records = array();
			
			$group_tags = $called_class::$api_data['xml'];
			
			//Método
			if(!in_array($method, $called_class::$api_data['methods']))
				self::throw_error(self::ERROR_INVALID_METHOD, $type);
			
			switch($method){
				case 'list': //Lista todos os registros
					if($sys_control->get_url_size() != 3)
						self::throw_error(self::ERROR_INVALID_PARAMS, $type);
					
					$objects = $called_class::load_all('', 0, 0, false, true);
					$records = $objects['results'];
					
					break;
				
				case 'get': //Carrega um registro específico
					if($sys_control->get_url_size() != 4)
						self::throw_error(self::ERROR_INVALID_PARAMS, $type);
					
					$id = $sys_control->get_page(3);
					$object = new $called_class($id, true);
					
					if(!$object->get('valid'))
						self::throw_error(self::ERROR_INVALID_RECORD, $type);
					
					$records = array($object);
					
					break;
				
				case 'search': //Busca um registro
					if($sys_control->get_url_size() != 4)
						self::throw_error(self::ERROR_INVALID_PARAMS, $type);
					
					$query = \Security\Sanitizer::sanitize(urldecode(\Storage\File::name($sys_control->get_page(3))));
					
					$search = $called_class::search($query, array(), array(), 0, 0, array(), false, true);
					$records = $search['results'];
					
					break;
				
				case 'delete': //Apaga um registro
					if($sys_control->get_url_size() != 4)
						self::throw_error(self::ERROR_INVALID_PARAMS, $type);
					
					$id = $sys_control->get_page(3);
					$object = new $called_class($id);
					
					$ret_obj = new \stdClass();
					$delete_result = $object->delete(true);
					
					if($delete_result === true){
						$ret_obj->status->success = 'true';
					}
					else{
						if(empty($delete_result))
							self::throw_error(self::ERROR_INVALID_RECORD, $type);
						
						$ret_obj->status->success = 'false';
						$ret_obj->status->error = addslashes(strip_tags($delete_result));
					}
					
					$group_tags = array();
					$records = array($ret_obj);
					
					break;
			}
			
			$result = array();
			$records_count = sizeof($records);
			
			if($records_count){
				for($i = 0; $i < $records_count; $i++)
					$result[$i] = \Database\DatabaseObject::to_array($records[$i]);
			}
			
			switch($type){
				case '': //JSON
				case 'json':
					header('Content-type: application/json');
					echo json_encode($result);
					
					break;
				
				case 'xml': //XML
					$xml = new \XML\XML($result, $group_tags);
					$xml->output();
					
					break;
				
				default: //Tipo inválido
					self::throw_error(self::ERROR_INVALID_TYPE, $type);
			}
			
			exit;
		}
		
		/**
		 * Dispara um erro na chamada da API.
		 * 
		 * @param int $code Codigo do erro.
		 * @param string $type Tipo de retorno, que pode ser 'json' ou 'xml'.
		 */
		public static function throw_error($code, $type = 'json'){
			global $sys_language;
			
			//Mensagem de erro
			switch($code){
				case self::ERROR_INVALID_PARAMS:
					$error = $sys_language->get('api', 'error_invalid_params');
					break;
				
				case self::ERROR_INVALID_TYPE:
					$error = $sys_language->get('api', 'error_invalid_type');
					break;
				
				case self::ERROR_INVALID_METHOD:
					$error = $sys_language->get('api', 'error_invalid_method');
					break;
				
				case self::ERROR_INVALID_CALL:
					$error = $sys_language->get('api', 'error_invalid_call');
					break;
				
				case self::ERROR_INVALID_RECORD:
					$error = $sys_language->get('api', 'error_invalid_record');
					break;
			}
			
			$result = array("error" => array("code" => $code, "message" => $error));
			$type = strtolower($type);
			
			switch($type){
				case 'xml': //XML
					$xml = new \XML\XML($result, array());
					$xml->output();
					
					break;
				
				case 'json': //JSON
				default:
					header('Content-type: application/json');
					echo json_encode($result);
			}
		}
	}
?>