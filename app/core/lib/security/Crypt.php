<?php
	namespace Security;
	
	/**
	 * Classe para criptografia/decriptografia.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 10/03/2014
	*/
	
	abstract class Crypt{
		const RANGE = 255;
		
		private static $constants = array(69, 38, 37, 54, 59, 64, 125, 42, 74, 112, 124, 87, 45, 138, 35, 121, 36, 102, 93, 33);
		
		/**
		 * Criptografa um valor.
		 * 
		 * @param string $src Valor a ser criptografado.
		 * @return string Valor criptografado.
		 */
		public static function exec($src){
			if(empty($src))
				return '';
			
			//Gera a chave
			$key = self::generate_key();
			
			//Inicia as variáveis
			$key_len = strlen($key);
			$key_pos = -1;
			$src_asc = 0;
			$src_pos = 0;
			$offset = mt_rand(0, self::RANGE);
			$result = str_pad(strtoupper(dechex($offset)), 2, '0', STR_PAD_LEFT);
			
			//Criptografa o valor
			for($src_pos = 0; $src_pos < strlen($src); $src_pos++){
				$src_asc = (ord($src[$src_pos]) + $offset) % self::RANGE;
				$key_pos = ($key_pos < ($key_len - 1)) ? $key_pos + 1 : 0;
				$src_asc ^= ord($key[$key_pos]);
				$result .= str_pad(strtoupper(dechex($src_asc)), 2, '0', STR_PAD_LEFT);
				$offset = $src_asc;
			}
			
			return $result;
		}
		
		/**
		 * Decriptografa um valor.
		 * 
		 * @param string $src Valor a ser decriptografado.
		 * @return string Valor decriptografado.
		 */
		public static function undo($src){
			if(empty($src))
				return '';
			
			//Gera a chave
			$key = self::generate_key();
			
			//Inicia as variáveis
			$result = '';
			$key_len = strlen($key);
			$key_pos = 0;
			$src_asc = 0;
			$src_pos = 2;
			$tmp_src_asc = 0;
			$offset = hexdec(substr($src, 0, 2));

			do{
				$src_asc = hexdec(substr($src, $src_pos, 2));
				$tmp_src_asc = ($src_asc ^ ord($key[$key_pos]));
				$key_pos = ($key_pos < ($key_len - 1)) ? $key_pos + 1 : 0;
				$tmp_src_asc = ($tmp_src_asc <= $offset) ? (self::RANGE + $tmp_src_asc - $offset) : ($tmp_src_asc - $offset);
				$result .= chr($tmp_src_asc);
				$offset = $src_asc;
				$src_pos = ($src_pos + 2);
			} while($src_pos < strlen($src));
			
			return $result;
		}
		
		/**
		 * Gera a chave de criptografia.
		 * 
		 * @return string Chave.
		 */
		private static function generate_key(){
			$key = '';
			
			foreach(self::$constants as $constant)
				$key .= chr($constant);
			
			return $key;
		}
	}
?>