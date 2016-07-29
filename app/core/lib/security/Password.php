<?php
	namespace Security;
	
	/**
	 * Classe para métodos de segurança do sistema.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 10/03/2014
	*/
	
	abstract class Password{
		/**
		 * Gera um senha aleatória alternando entre números (de 0 a 9) e caracteres (de 'a' a 'z').
		 * 
		 * @param int $length Quantidade de caracteres da senha.
		 * @return string Senha gerada.
		 */
		public static function generate($length = 6){
			$password = '';
			
			for($s = 1; $s <= $length; $s++)
				$password .= !($s % 2) ? chr(mt_rand(97, 122)) : mt_rand(0, 9);
			
			return $password;
		}
	}
?>