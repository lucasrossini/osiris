<?php
	namespace Formatter;
	
	/**
	 * Classe para formatação de valores numéricos.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 24/01/2014
	*/
	
	abstract class Number{
		/**
		 * Insere zeros à esquerda de um número.
		 * 
		 * @param int $value Número a ser formatado.
		 * @param int $padding Quantidade de zeros a serem inseridos.
		 * @return string Número formatado.
		 */
		public static function zero_padding($value, $padding){
			return str_pad($value, $padding, '0', STR_PAD_LEFT);
		}
		
		/**
		 * Formata o valor em unidade monetária (formato 'R$ 0.000,00').
		 * 
		 * @param float $value Valor a ser formatado.
		 * @param string $unit Unidade monetária a ser utilizada, sendo colocada como prefixo do valor formatado.
		 * @return string Valor formatado.
		 */
		public static function money($value, $unit = 'R$'){
			$money = number_format((float)$value, 2, ',', '.');
			
			if(!empty($unit))
				$money = $unit.' '.$money;
			
			return $money;
		}
		
		/**
		 * Converte um valor numérico no formato '0000.00' para o formato '0.000,00'
		 * 
		 * @param float $value Valor a ser formatado.
		 * @param int $decimals Quantidade de casas decimais.
		 * @param boolean $force Define se a formatação deve ser realizada independente do valor estar vazio.
		 * @return string Valor formatado.
		 */
		public static function sql2number($value, $decimals = 2, $force = false){
			if(!empty($value) || $force)
				return number_format((float)$value, $decimals, ',', '.');
		}
		
		/**
		 * Converte um valor numérico no formato '0.000,00' para o formato '0000.00'.
		 * 
		 * @param string $value Valor a ser formatado.
		 * @return float Valor numérico formatado.
		 */
		public static function number2sql($value){
			return !is_float($value) ? (float)str_replace(',', '.', str_replace('.', '', $value)) : $value;
		}
	}
?>