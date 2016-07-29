<?php
	namespace HTTP;
	
	/**
	 * Classe que captura dados importantes do servidor.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 31/07/2013
	*/
	
	abstract class Server{
		/**
		 * Verifica se o servidor é a máquina local.
		 * 
		 * @return boolean TRUE caso seja máquina local ou FALSE caso não seja máquina local.
		 */
		public static function is_local(){
			return ($_SERVER['SERVER_ADDR'] == LOCALHOST_IP);
		}
		
		/**
		 * Verifica se o servidor é o servidor local.
		 * 
		 * @return boolean TRUE caso seja servidor local ou FALSE caso não seja servidor local.
		 */
		public static function is_local_server(){
			return ($_SERVER['SERVER_ADDR'] == LOCAL_SERVER_IP);
		}
		
		/**
		 * Verifica se o servidor é o servidor web, ou seja, não é máquina local e nem servidor local.
		 * 
		 * @return boolean TRUE caso seja servidor web ou FALSE caso não seja servidor web.
		 */
		public static function is_web_server(){
			return !(self::is_local() || self::is_local_server());
		}
	}
?>