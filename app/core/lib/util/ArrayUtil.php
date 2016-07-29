<?php
	namespace Util;
	
	/**
	 * Classe para manipulação de vetores.
	 *
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 07/03/2014
	 */
	
	abstract class ArrayUtil{
		/**
		 * Monta texto com a contagem de palavras do vetor.
		 *
		 * @param array $items Vetor com as palavras a serem contadas.
		 * @param string $separator Caractere que separa os itens.
		 * @param string $last Palavra que separa o último item dos demais.
		 * @param int $limit Quantidade máxima de itens a serem exibidos.
		 * @param string $truncate Texto exibido após o corte.
		 * @param boolean $ending Define se o último item a ser contado deve ser precedido da palavra $last. Senão, é precedido pelo padrão $separator.
		 * @param boolen $prevent_repeat Impede contagem de itens repetidos.
		 * @return string Contagem de palavras do vetor.
		 * @example ArrayUtil::count_items(array('item 1', 'item 2', 'item 3')); //Resultado: 'item 1, item 2 e item 3'
		 */
		public static function count_items($items = array(), $separator = ', ', $last = '', $limit = 0, $truncate = '...', $ending = true, $prevent_repeat = false){
			$string = '';
			
			if(sizeof($items)){
				global $sys_language;
				
				if(empty($last))
					$last = ' '.$sys_language->get('class_array_util', 'last_and').' ';
				
				$already = array();
				$i = 0;
				
				//Retira os itens em branco e repetidos, caso necessário
				$aux = array();
				
				foreach($items as $item){
					if(!empty($item) && (!$prevent_repeat || ($prevent_repeat && !in_array($item, $already)))){
						$aux[] = $item;
						$already[] = $item;
					}
				}
				
				$items = $aux;
				
				//Conta os itens
				$items_count = sizeof($items);
				
				foreach($items as $item){
					$i++;
					
					if($items_count === 1){
						$string = $item;
					}
					elseif(($i === $limit) && ($i < $items_count)){
						$string .= $separator.$item.$truncate;
						break;
					}
					else{
						if(($i == $items_count) && $ending)
							$string .= $last.$item;
						else
							$string .= $separator.$item;
					}
				}
			}
			
			return ltrim($string, $separator);
		}
		
		/**
		 * Transforma um vetor em parâmetros GET.
		 *
		 * @param array $array Vetor de parâmetros, onde a chave é o nome do parâmetro e o valor é o valor do parâmetro.
		 * @param boolean $first Indica se os parâmetros serão os primeiros, iniciando a sequência com o caracter '?'.
		 * @return string Sequência de parâmetros.
		 */
		public static function paramify($array = array(), $first = true){
			$params = '';
			
			if(sizeof($array)){
				$params = $first ? '?' : '&';
				
				foreach($array as $key => $value)
					$params .= $key.'='.$value.'&';
				
				$params = rtrim($params, '&');
			}
			
			return $params;
		}
		
		/**
		 * Transforma um vetor em listas, ordenadas ou não.
		 * 
		 * @param array $array Vetor com os itens a serem listados.
		 * @param int $columns Número de colunas (listas diferentes).
		 * @param boolean $ordered Indica se a lista deve ser do tipo ordenada.
		 * @return string HTML com as listas montadas.
		 */
		public static function listify($array = array(), $columns = 1, $ordered = false){
			$html = '';
			$list_type = $ordered ? 'ol' : 'ul';
			$array_size = sizeof($array);
			
			if($array_size){
				$items_per_column = round($array_size / $columns);
				$html .= '<'.$list_type.'>';
				$i = $j = 0;
				
				foreach($array as $item){
					$i++;
					$j++;
					$closed = false;
					
					$html .= '<li>'.$item.'</li>';
					
					if($i == $items_per_column){
						$i = 0;
						$html .= '</'.$list_type.'>';
						$closed = true;
						
						if($j < $array_size){
							$last = (($array_size - $j) <= $items_per_column) ? 'last' : '';
							$html .= '<'.$list_type.' class="'.$last.'">';
						}
					}
				}
				
				if(!$closed)
					$html .= '</'.$list_type.'>';
			}
			
			return $html;
		}
		
		/**
		 * Verifica se o vetor está totalmente vazio.
		 *
		 * @param array $array Vetor a ser validado.
		 * @return boolean TRUE caso esteja vazio ou FALSE caso não esteja vazio.
		 */
		public static function is_empty($array){
			$empty = false;
				
			if(is_array($array)){
				$empty = true;
				
				if(sizeof($array)){
					foreach($array as $item){
						if(is_array($item)){
							if(!self::is_empty($item)){
								$empty = false;
								break;
							}
						}
						else{
							$item = trim($item);

							if(!empty($item)){
								$empty = false;
								break;
							}
						}
					}
				}
			}
				
			return $empty;
		}
		
		/**
		 * Verifica se o array está totalmente cheio.
		 *
		 * @param array $array Vetor a ser validado.
		 * @return boolean TRUE caso esteja cheio ou FALSE caso não esteja cheio.
		 */
		public static function is_full($array){
			$i = 0;
				
			if(is_array($array) && sizeof($array)){
				foreach($array as $item){
					if(is_array($item)){
						if(self::is_full($item))
							$i++;
					}
					else{
						$item = trim($item);
							
						if(!empty($item))
							$i++;
					}
				}
			}
				
			return ($i === sizeof($array));
		}
		
		/**
		 * Transforma um objeto em um vetor.
		 * 
		 * @param object $data Objeto a ser transformado.
		 * @return array Vetor do objeto.
		 */
		public static function obj2array($data){
			if(is_object($data))
				$data = get_object_vars($data);
			
			return is_array($data) ? array_map(__METHOD__, $data) : $data;
		}
		
		/**
		 * Transforma um vetor em um objeto.
		 * 
		 * @param array $data Vetor a ser transformado.
		 * @return object Objeto do vetor.
		 */
		public static function array2obj($data){
			return is_array($data) ? (object)array_map(__METHOD__, $data) : $data;
		}
		
		/**
		 * Remove todas as ocorrências de um elemento de um vetor.
		 * 
		 * @param mixed $needle Elemento a ser removido.
		 * @param array $haystack Vetor a ser processado.
		 * @param boolean $preserve_keys Define se as chaves do vetor devem ser preservadas.
		 * @return array Vetor com o elemento removido.
		 */
		public static function remove($needle, $haystack, $preserve_keys = false){
			$i = 0;
			$array = array();
			
			foreach($haystack as $key => $value){
				if(is_string($value))
					$value = trim($value);
				
				if($needle != $value){
					$array[$preserve_keys ? $key : $i] = $value;
					$i++;
				}
			}
			
			return $array;
		}
	}
?>