<?php
	namespace HTTP;
	
	/**
	 * Classe para manipulação de parâmetros GET/POST.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 10/01/2013
	*/
	
	abstract class Request{
		/**
		 * Carrega um parâmetro GET.
		 * 
		 * @param string $var Nome do parâmetro.
		 * @param boolean $decode Define se o valor deve ser decodificado (url_decode).
		 * @return mixed Valor do parâmetro.
		 */
		public static function get($var, $decode = true){
			$var = \Security\Sanitizer::sanitize($_GET[$var]);
			
			if($decode && !is_array($var) && !is_object($var))
				$var = urldecode($var);
			
			return $var;
		}
		
		/**
		 * Carrega um parâmetro POST.
		 * 
		 * @param string $var Nome do parâmetro.
		 * @param boolean $anti_injection Define se o valor deve ser tratado para bloquear SQL injection.
		 * @return mixed Valor do parâmetro.
		 */
		public static function post($var, $anti_injection = true){
			return $anti_injection ? \Security\Sanitizer::sanitize($_POST[$var]) : $_POST[$var];
		}
		
		/**
		 * Define o valor de um parâmetro GET/POST.
		 * 
		 * @param string $type Tipo de parâmetro, que pode ser 'get' ou 'post'.
		 * @param string $var Nome do parâmetro.
		 * @param mixed $value Valor a ser atribuído ao parâmetro.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function set($type, $var, $value){
			switch(strtoupper($type)){
				case 'GET':
					$_GET[$var] = $value;
					break;
				
				case 'POST':
					$_POST[$var] = $value;
					break;
				
				default:
					return false;
			}
			
			return true;
		}
		
		/**
		 * Verifica se um ou mais parâmetros GET/POST foram definidos.
		 * 
		 * @param string $type Tipo de parâmetro, que pode ser 'get' ou 'post'.
		 * @param string|array $var Nome do parâmetro ou vetor com os nomes dos parâmetros.
		 * @return boolean TRUE caso foi definido ou FALSE caso não foi definido.
		 */
		public static function is_set($type, $var){
			switch(strtoupper($type)){
				case 'GET':
					if(is_array($var)){
						foreach($var as $var_name){
							if(!isset($_GET[$var_name]))
								return false;
						}
					
						return true;
					}
					else{
						return isset($_GET[$var]);
					}
					
					break;
				
				case 'POST':
					if(is_array($var)){
						foreach($var as $var_name){
							if(!isset($_POST[$var_name]))
								return false;
						}
							
						return true;
					}
					else{
						return isset($_POST[$var]);
					}
					
					break;
				
				default:
					return false;
			}
		}
		
		/**
		 * Verifica se uma requisição foi realizada através de AJAX.
		 * 
		 * @return boolean TRUE caso seja uma requisição AJAX ou FALSE caso não seja uma requisição AJAX.
		 */
		public static function is_ajax(){
			return (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
		}
	}
?>