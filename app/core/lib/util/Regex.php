<?php
	namespace Util;
	
	/**
	 * Classe para expressões regulares.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 21/11/2012
	*/
	
	abstract class Regex{
		/**
		 * Captura o valor entre colchetes.
		 * 
		 * @param string $string Texto a ser processado.
		 * @return array Vetor contendo os valores entre colchetes encontrados no texto.
		 */
		public static function extract_brackets($string){
			$matches = $result = array();
			preg_match_all('/\[([^\]]*)\]/', $string, $matches);
			
			foreach($matches[1] as $match)
				$result[] = $match;
			
			return $result;
		}
		
		/**
		 * Captura o valor entre parênteses.
		 * 
		 * @param string $string Texto a ser processado.
		 * @return array Vetor contendo os valores entre parênteses encontrados no texto.
		 */
		public static function extract_parenthesis($string){
			$matches = $result = array();
			preg_match_all('#\((([^()]+|(?R))*)\)#', $string, $matches);
			
			foreach($matches[1] as $match)
				$result[] = $match;
			
			return $result;
		}
	}
?>