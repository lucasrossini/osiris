<?php
	namespace HTTP;
	
	/**
	 * Classe para manipulação de sessões.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 29/06/2012
	*/
	
	abstract class Session{
		/**
		 * Cria uma sessão.
		 * 
		 * @param string $name Nome da sessão.
		 * @param mixed $value Valor a ser atribuído para a sessão.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function create($name, $value){
			return $_SESSION[$name] = $value;
		}
		
		/**
		 * Apaga uma ou mais sessões.
		 * 
		 * @param string|array $name Nome da sessão ou vetor com os nomes das sessões.
		 * @return boolean TRUE.
		 */
		public static function delete($name){
			if(is_array($name)){
				foreach($name as $session_name)
					unset($_SESSION[$session_name]);
			}
			else{
				unset($_SESSION[$name]);
			}
			
			return true;
		}
		
		/**
		 * Verifica se uma ou mais sessões estão definidas.
		 * 
		 * @param string|array $name Nome da sessão ou vetor com os nomes das sessões.
		 * @return boolean TRUE caso estejam definidas ou FALSE caso não estejam definidas.
		 */
		public static function exists($name){
			if(is_array($name)){
				foreach($name as $session_name){
					if(!isset($_SESSION[$session_name]))
						return false;
				}
				
				return true;
			}
			else{
				return isset($_SESSION[$name]);
			}
		}
		
		/**
		 * Carrega o valor de uma sessão.
		 * 
		 * @param string $name Nome da sessão.
		 * @return mixed Valor da sessão.
		 */
		public static function get($name){
			return self::exists($name) ? $_SESSION[$name] : null;
		}
		
		/**
		 * Destrói toda a sessão atual.
		 */
		public static function destroy(){
			return @session_destroy();
		}
	}
?>