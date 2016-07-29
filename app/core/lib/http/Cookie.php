<?php
	namespace HTTP;
	
	/**
	 * Classe para manipulação de cookies.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 16/10/2013
	*/
	
	abstract class Cookie{
		/**
		 * Cria um cookie.
		 * 
		 * @param string $name Nome do cookie a ser criado.
		 * @param mixed $value Valor a ser gravado no cookie.
		 * @param int $expires Tempo de expiração do cookie (em segundos)
		 * @param string $path Diretório que terá acesso ao cookie.
		 * @return boolean TRUE em caso de sucesso e FALSE em caso de falha.
		 */
		public static function create($name, $value, $expires = COOKIE_EXPIRE){
			return setcookie($name, $value, $expires, rtrim(DIR, '/').'/');
		}
		
		/**
		 * Apaga um cookie.
		 * 
		 * @param string|array $name Nome do cookie ou vetor com os nomes dos cookies a serem apagados.
		 * @return boolean TRUE em caso de sucesso e FALSE em caso de falha.
		 */
		public static function delete($name){
			if(is_array($name)){
				foreach($name as $cookie_name)
					self::delete($cookie_name);
				
				return true;
			}
			else{
				return setcookie($name, '', time() - 3600, '/');
			}
		}
		
		/**
		 * Verifica se um cookie existe.
		 * 
		 * @param string $name Nome do cookie a ser verificado.
		 * @return boolean TRUE caso o cookie exista e FALSE caso o cookie não exista.
		 */
		public static function exists($name){
			return isset($_COOKIE[$name]);
		}
		
		/**
		 * Carrega o valor de um cookie.
		 * 
		 * @param string $name Nome do cookie a ser carregado.
		 * @return mixed Valor carregado.
		 */
		public static function get($name){
			return self::exists($name) ? $_COOKIE[$name] : null;
		}
	}
?>