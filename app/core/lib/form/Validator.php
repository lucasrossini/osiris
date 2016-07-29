<?php
	namespace Form;
	
	/**
	 * Classe para validação.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 18/10/2013
	*/
	
	abstract class Validator{
		/**
		 * Valida um e-mail.
		 * 
		 * @param string $email Endereço de e-mail a ser validado.
		 * @return boolean TRUE caso seja válido ou FALSE caso seja inválido.
		 */
		public static function is_email($email){
			return filter_var($email, FILTER_VALIDATE_EMAIL);
		}
		
		/**
		 * Valida uma URL.
		 * 
		 * @param string $url URL a ser validada.
		 * @return boolean TRUE caso seja válida ou FALSE caso seja inválida.
		 */
		public static function is_url($url){
			return preg_match("/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i", $url);
		}
		
		/**
		 * Valida uma data no formato '00/00/0000' (ou '0000/00/00', caso o sistema esteja em inglês).
		 * 
		 * @param string $date Data a ser validada.
		 * @return boolean TRUE caso seja válida ou FALSE caso seja inválida.
		 */
		public static function is_date($date){
			$is_eng = (\System\Language::get_current_lang() == 'en');
			$date_pieces = explode('/', $date);
			
			$day = !$is_eng ? $date_pieces[0] : $date_pieces[2];
			$month = $date_pieces[1];
			$year = !$is_eng ? $date_pieces[2] : $date_pieces[0];
			
			return checkdate((int)$month, (int)$day, (int)$year);
		}
		
		/**
		 * Valida uma hora (formato '00:00:00').
		 * 
		 * @param string $time Hora a ser validada.
		 * @return boolean TRUE caso seja válida ou FALSE caso seja inválida.
		 */
		public static function is_time($time){
			$time_pieces = explode(':', $time);
			return ((($time_pieces[0] >= 0) && ($time_pieces[0] <= 23)) && (($time_pieces[1] >= 0) && ($time_pieces[1] <= 59)) && (($time_pieces[2] >= 0) && ($time_pieces[2] <= 59))) ? true : false;
		}
		
		/**
		 * Valida um mês (entre 1 e 12).
		 * 
		 * @param int $month Número do mês a ser validado.
		 * @return boolean TRUE caso seja válido ou FALSE caso seja inválido.
		 */
		public static function is_month($month){
			return self::between($month, 1, 12);
		}
		
		/**
		 * Valida um ano (entre 1000 e 3000).
		 * 
		 * @param int $year Ano a ser validado.
		 * @return boolean TRUE caso seja válido ou FALSE caso seja inválido.
		 */
		public static function is_year($year){
			return self::between($year, 1000, 3000);
		}
		
		/**
		 * Valida uma imagem.
		 *
		 * @param string $source Caminho completo do arquivo de imagem a ser validado.
		 * @param array $extensions Vetor com as extensões de arquivos de imagem a serem validados.
		 * @return boolean TRUE caso seja válida ou FALSE caso seja inválida.
		 */
		public static function is_image($source, $extensions = array()){
			$valid = true;
			$info = getimagesize($source);
			
			if(empty($info)){
				$valid = false;
			}
			else{
				$mime = $info['mime'];
				$mime_pieces = explode('/', $mime);
				
				if(!(($mime_pieces[0] == 'image') && in_array($mime_pieces[1], $extensions)))
					$valid = false;
			}
			
			return $valid;
		}
		
		/**
		 * Valida um intervalo de valores.
		 * 
		 * @param int $value Valor a ser validado.
		 * @param int $min Valor mínimo do intervalo.
		 * @param int $max Valor máximo do intervalo.
		 * @return boolean TRUE caso seja válido ou FALSE caso seja inválido.
		 */
		public static function between($value, $min, $max){
			return (((int)$value >= (int)$min) && ((int)$value <= (int)$max));
		}
		
		/**
		 * Verifica se o texto possui caracteres especiais.
		 * 
		 * @param string $string Texto a ser verificado.
		 * @return boolean TRUE caso possua caracteres especiais ou FALSE caso não possua caracteres especiais.
		 */
		public static function has_special_chars($string){
			$string_length = strlen($string);
			
			for($c = 0; $c < $string_length; $c++){
				$char = substr($string, $c, 1);
				$ascii = ord($char);
				
				if((($ascii < 48) || ($ascii > 57)) && (($ascii < 97) || ($ascii > 122)) && ($ascii != 95) && ($ascii != 46))
					return true;
			}
			
			return false;
		}
		
		/**
		 * Verifica se o texto possui HTML.
		 * 
		 * @param string $string Texto a ser verificado.
		 * @return boolean TRUE caso possua HTML ou FALSE caso não possua HTML.
		 */
		public static function has_html($string){
			return ($string != strip_tags($string));
		}
		
		/**
		 * Verifica a proporção de caracteres maiúsculos em um texto.
		 * 
		 * @param string $string Texto a ser verificado.
		 * @return int Porcentagem de caracteres maiúsculos no texto.
		 */
		public static function check_uppercase_count($string = ''){
			$uppercase_count = $total_count = $proportion = 0;
			$string = \Formatter\String::remove_accent($string);
			$string_length = strlen($string);
			
			if(strlen($string)){
				for($c = 0; $c < $string_length; $c++){
					$char = substr($string, $c, 1);
					$ascii = ord($char);
					
					if(($ascii >= 65) && ($ascii <= 90)){
						$uppercase_count++;
						$total_count++;
					}
					
					if(($ascii >= 97) && ($ascii <= 122))
						$total_count++;
				}
				
				$proportion = round(($uppercase_count * 100) / $total_count);
			}
			
			return $proportion;
		}
		
		/**
		 * Verifica a proporção de caracteres minúsculos em um texto.
		 * 
		 * @param string $string Texto a ser verificado.
		 * @return int Porcentagem de caracteres minúsculos no texto.
		 */
		public static function check_lowercase_count($string = ''){
			$lowercase_count = $total_count = $proportion = 0;
			$string = \Formatter\String::remove_accent($string);
			$string_length = strlen($string);
			
			if(strlen($string)){
				for($c = 0; $c < $string_length; $c++){
					$char = substr($string, $c, 1);
					$ascii = ord($char);
					
					if(($ascii >= 97) && ($ascii <= 122)){
						$lowercase_count++;
						$total_count++;
					}
					
					if(($ascii >= 65) && ($ascii <= 90))
						$total_count++;
				}
				
				$proportion = round(($lowercase_count * 100) / $total_count);
			}
			
			return $proportion;
		}
		
		/*-- Validação de CPF e CNPJ --*/
		
		/**
		 * Remove '.', '-', e '/' de um CPF/CNPJ.
		 * 
		 * @param string $string CPF/CNPJ a ser tratado.
		 * @return string CPF/CNPJ contendo somente números.
		 */
		private static function replace($string){
			return trim(str_replace('/', '', str_replace('-', '', str_replace('.', '', $string))));
		}
		
		/**
		 * Verifica se um CPF/CNPJ é falso (contém todos os números iguais).
		 * 
		 * @param string $string CPF/CNPJ a ser verificado.
		 * @param int $length Tamanho do número a ser verificado (11 para CPF ou 14 para CNPJ).
		 * @return boolean TRUE caso seja falso ou FALSE caso seja verdadeiro.
		 */
		private static function check_fake($string, $length){
			for($i = 0; $i <= 9; $i++){
				$fake = str_pad('', $length, $i);
				
				if($string === $fake)
					return true;
			}
			
			return false;
		}
		
		/**
		 * Valida um CPF.
		 * 
		 * @param string $cpf CPF a ser validado.
		 * @return boolean TRUE caso seja válido ou FALSE caso seja inválido.
		 */
		public static function is_cpf($cpf){
			$cpf = self::replace($cpf);
			
			if(empty($cpf) || (strlen($cpf) != 11)){
				return false;
			}
			else{
				if(self::check_fake($cpf, 11)){
					return false;
				}
				else{
					$sub_cpf = substr($cpf, 0, 9);
					
					for($i = 0; $i <= 9; $i++)
						$dv += ($sub_cpf[$i] * (10-$i));
					
					if($dv == 0)
						return false;
					
					$dv = 11 - ($dv % 11);
					
					if($dv > 9)
						$dv = 0;
					
					if($cpf[9] != $dv)
						return false;
		
					$dv *= 2;
					
					for($i = 0; $i <= 9; $i++)
						$dv += ($sub_cpf[$i] * (11 - $i));
					
					$dv = 11 - ($dv % 11);
					
					if($dv > 9)
						$dv = 0;
					
					if($cpf[10] != $dv)
						return false;
					
					return true;
				}
			}
		}
		
		/**
		 * Valida um CNPJ.
		 *
		 * @param string $cnpj CNPJ a ser validado.
		 * @return boolean TRUE caso seja válido ou FALSE caso seja inválido.
		 */
		public static function is_cnpj($cnpj){
			$cnpj = self::replace($cnpj);
			
			if(empty($cnpj) || (strlen($cnpj) != 14)){
				return false;
			}
			else{
				if(self::check_fake($cnpj, 14)){
					return false;
				}
				else{
					$rev_cnpj = strrev(substr($cnpj, 0, 12));
					
					for($i = 0; $i <= 11; $i++){
						$i == 0 ? $multiplier = 2 : $multiplier;
						$i == 8 ? $multiplier = 2 : $multiplier;
						$multiply = ($rev_cnpj[$i] * $multiplier);
						$sum = $sum + $multiply;
						$multiplier++;
					}
					
					$rest = $sum % 11;
					
					if($rest == 0 || $rest == 1)
						$dv1 = 0;
					else
						$dv1 = 11 - $rest;
		
					$sub_cnpj = substr($cnpj, 0, 12);
					$rev_cnpj = strrev($sub_cnpj.$dv1);
					unset($sum);
					
					for($i = 0; $i <= 12; $i++){
						$i == 0 ? $multiplier = 2 : $multiplier;
						$i == 8 ? $multiplier = 2 : $multiplier;
						$multiply = ($rev_cnpj[$i] * $multiplier);
						$sum = $sum + $multiply;
						$multiplier++;
					}
					
					$rest = $sum % 11;
					
					if($rest == 0 || $rest == 1)
						$dv2 = 0;
					else
						$dv2 = 11 - $rest;
					
					if($dv1 == $cnpj[12] && $dv2 == $cnpj[13])
						return true;
					else
						return false;
				}
			}
		}
	}
?>