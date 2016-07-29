<?php
	namespace Security;
	
	/**
	 * Classe para limpeza e tratamento de valores.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 17/03/2014
	*/
	
	abstract class Sanitizer{
		/**
		 * Trata os caracteres especiais para evitar SQL Injection.
		 * 
		 * @param mixed $var Valor/vetor de valores a ser tratado.
		 * @param string $var_name Nome da variável passada para o método.
		 * @param array $params Vetor com parâmetros a serem passados para o método quando é chamado através de callback, onde a chave é o nome do parâmetro e o valor é o valor do parâmetro.
		 * @return mixed Valor tratado.
		 */
		public static function sanitize(&$var, $var_name = '', $params = array('replace_var' => false)){
			$aux = $var;
			
			if(!is_object($aux))
				$aux = !is_array($aux) ? trim(str_replace(array('"', "'", '\\'), array('&quot;', '&#39;', '&#92;'), $aux)) : array_map(__METHOD__, $aux);
			
			if($params['replace_var'])
				$var = $aux;
			
			return $aux;
		}
		
		/**
		 * Transforma entidade HTML das aspas simples e duplas para seus caracteres verdadeiros.
		 * 
		 * @param string $var Valor a ser tratado.
		 * @return string Valor tratado.
		 */
		public static function restore($var){
			return str_replace(array('&quot;', '&#39;', '&#92;'), array('"', "'", '\\'), $var);
		}
	}
?>