<?php
	namespace UI;
	
	/**
	 * Classe para manipulação de elementos HTML.
	 *
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 21/11/2013
	 */
	
	abstract class HTML{
		/**
		 * Monta atributos de um elemento HTML.
		 * 
		 * @param array $params Vetor com os atributos a serem montados, onde a chave é o nome do atributo e o valor é o valor do atributo.
		 * @return string Atributos montados em texto.
		 */
		public static function prepare_attr($params = array()){
			$attrs = '';
			
			if(is_array($params) && sizeof($params)){
				foreach($params as $key => $value){
					if(!empty($key))
						$attrs .= $key.'="'.$value.'" ';
				}
			}
			
			return rtrim($attrs);
		}
		
		/**
		 * Remove todas as tags HTML em branco de um texto.
		 *
		 * @param string $string Texto a ser processado.
		 * @return string Texto sem as tags HTML em branco.
		 */
		public static function strip_empty_tags($string){
			return preg_replace('/<[^\/>]*>([\s]?)*<\/[^>]*>/', '', $string);
		}
		
		/**
		 * Substitui o conteúdo de uma tag HTML.
		 *
		 * @param string $tag HTML da tag a ser processada.
		 * @param string $content Novo conteúdo a ser inserido na tag HTML.
		 * @return string HTML da tag HTML com o novo conteúdo.
		 */
		public static function replace_tag_content($tag, $content){
			return preg_match('/(<[^>]*>)([^<]+)(<\/[^>]*>)/', $tag) ? preg_replace('/(<[^>]*>)([^<]+)(<\/[^>]*>)/', "$1{$content}$3", $tag) : $content;
		}
		
		/**
		 * Fecha as tags HTML abertas.
		 * 
		 * @param string $html Texto HTML a ser processado.
		 * @return string Texto HTML com as tags fechadas.
		 */
		public static function close_tags($html){
			//Carrega as tags abertas
			preg_match_all('#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
			$opened_tags = $result[1];
			
			//Carrega as tags fechadas
			preg_match_all('#</([a-z]+)>#iU', $html, $result);
			$closed_tags = $result[1];
			
			//Todas as tags estão fechadas
			if(sizeof($closed_tags) == sizeof($opened_tags)){
				return $html;
			}
			
			//Fecha as tags abertas
			$opened_tags = array_reverse($opened_tags);
			
			for($i = 0; $i < sizeof($opened_tags); $i++){
				if(!in_array($opened_tags[$i], $closed_tags))
					$html .= '</'.$opened_tags[$i].'>';
				else
					unset($closed_tags[array_search($opened_tags[$i], $closed_tags)]);
			}
			
			return $html;
		}
		
		/**
		 * Identifica e coloca link nas URLs de um texto.
		 * 
		 * @param string $string Texto a ser processado.
		 * @param array $params Vetor contendo atributos extra a serem atribuídos ao link, onde a chave é o nome do atributo e o valor é o valor do atributo.
		 * @return string Texto com os links aplicados.
		 */
		public static function highlight_urls($string, $params = array('target' => '_blank')){
			$regex = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';
			
			if(preg_match($regex, $string, $url))
				return preg_replace($regex, '<a href="'.$url[0].'" '.self::prepare_attr($params).'>'.$url[0].'</a>', $string);
			
			return $string;
		}
		
		/**
		 * Identifica e coloca link nos endereços de e-mail de um texto.
		 *
		 * @param string $string Texto a ser processado.
		 * @param array $params Vetor contendo atributos extra a serem atribuídos ao link, onde a chave é o nome do atributo e o valor é o valor do atributo.
		 * @return string Texto com os links aplicados.
		 */
		public static function highlight_emails($string, $params = array()){
			return preg_replace('/([a-z0-9][_a-z0-9.-]+@([0-9a-z][_0-9a-z-]+\.)+[a-z]{2,6})/i','<a href="mailto:\\1" '.self::prepare_attr($params).'>\\1</a>', $string);
		}
		
		/**
		 * Realça um conjunto de palavras do texto (envolvendo as palavras realçadas por uma tag SPAN com a classe CSS 'text-word-highlight').
		 *
		 * @param string $text Texto a ser processado.
		 * @param array $words Vetor com as palavras a serem realçadas no texto.
		 * @return string Texto Texto com as palavras realçadas.
		 */
		public static function highlight_words($text, $words = array()){
			if(sizeof($words)){
				foreach($words as $word)
					$text = preg_replace("/\b({$word})\b/i", '<span class="text-word-highlight">\1</span>', $text);
			}
				
			return $text;
		}
	}
?>