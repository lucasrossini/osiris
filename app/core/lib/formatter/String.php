<?php
	namespace Formatter;
	
	/**
	 * Classe para formatação de strings.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 10/03/2014
	*/
	
	abstract class String{
		const GENDER_MALE = 1;
		const GENDER_FEMALE = 2;
		
		/**
		 * Gera o slug de um texto.
		 * 
		 * @param string $string Texto a ser transformado.
		 * @return string Slug do texto.
		 */
		public static function slug($string){
			return str_replace('_', '-', self::strip_special_chars($string));
		}
		
		/**
		 * Retira os acentos de um texto.
		 *
		 * @param string $string Texto a ser transformado.
		 * @return string Texto sem acentos.
		 */
		public static function remove_accent($string){
			$with = array('á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç','ñ','Á','À','Â','Ã','Ä','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Ô','Õ','Ö','Ú','Ù','Û','Ü','Ç','Ñ');
			$without = array('a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n','A','A','A','A','A','E','E','E','E','I','I','I','I','O','O','O','O','O','U','U','U','U','C','N');
			
			return str_replace($with, $without, $string);
		}
		
		/**
		 * Função strtolower sem problemas de acentuação.
		 *
		 * @param string $string Texto a ser transformado.
		 * @return string Texto em letras minúsculas.
		 */
		public static function strtolower($string){
			$array_lower = array('á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç','ñ');
			$array_upper = array('Á','À','Â','Ã','Ä','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Ô','Õ','Ö','Ú','Ù','Û','Ü','Ç','Ñ');
			
			return utf8_encode(strtolower(str_replace($array_upper, $array_lower, utf8_decode($string))));
		}
		
		/**
		 * Função strtoupper sem problemas de acentuação.
		 *
		 * @param string $string Texto a ser transformado.
		 * @return string Texto em letras maiúsculas.
		 */
		public static function strtoupper($string){
			$array_lower = array('á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç','ñ');
			$array_upper = array('Á','À','Â','Ã','Ä','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Ô','Õ','Ö','Ú','Ù','Û','Ü','Ç','Ñ');
			
			return utf8_encode(strtoupper(str_replace($array_lower, $array_upper, utf8_decode($string))));
		}
		
		/**
		 * Corta um texto.
		 *
		 * @param string $text Texto a ser cortado.
		 * @param int $length Quantidade máxima de caracteres do texto a serem exibidos antes do corte.
		 * @param string $ending Texto exibido após o corte.
		 * @param boolean $include_ending Define se o tamanho do texto exibido após o corte deve ser incluído no tamanho total do corte.
		 * @param boolean $strip_tags Define se as tags HTML devem ser removidas.
		 * @param boolean $show_title Define se um elemento SPAN com o atributo 'title' contendo o texto completo deve envolver o texto cortado.
		 * @param boolean $exact Define se o texto deve ser cortado na quantidade exata de caracteres, considerando os espaços, etc.
		 * @param boolean $consider_html Define se o HTML deve ser considerado, não fazendo parte do corte.
		 * @return string Texto cortado.
		 */
		public static function truncate($text, $length, $ending = '...', $include_ending = true, $strip_tags = true, $show_title = false, $exact = true, $consider_html = true){
			$title_text = strip_tags($text);
			
			if($strip_tags){
				$text = $title_text;
				$consider_html = false;
			}
			
			//Corrige as aspas do Word
			$text = str_replace(array('&ldquo;', '&rdquo;'), array('"', '"'), $text);
			
			//Transforma as entidades de caracteres especiais nos próprios caracteres
			$text = utf8_decode(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
			
			if($consider_html){
				if(mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length)
					return utf8_encode($text);
				
				$total_length = mb_strlen($ending);
				$open_tags = array();
				$truncate = '';
				
				preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
				
				foreach($tags as $tag){
					if(!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])){
						if(preg_match('/<[\w]+[^>]*>/s', $tag[0])){
							array_unshift($open_tags, $tag[2]);
						}
						elseif(preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $close_tag)){
							$pos = array_search($close_tag[1], $open_tags);
							
							if($pos !== false)
								array_splice($open_tags, $pos, 1);
						}
					}
					
					$truncate .= $tag[1];
					$content_length = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
					
					if($content_length + $total_length > $length){
						$left = $length - $total_length;
						$entities_length = 0;
						
						if(preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)){
							foreach($entities[0] as $entity){
								if($entity[1] + 1 - $entities_length <= $left){
									$left--;
									$entities_length += mb_strlen($entity[0]);
								}
								else{
									break;
								}
							}
						}
						
						$truncate .= mb_substr($tag[3], 0, $left + $entities_length);
						break;
					}
					else{
						$truncate .= $tag[3];
						$total_length += $content_length;
					}
					
					if($total_length >= $length)
						break;
				}
			}
			else{
				if(mb_strlen($text) <= $length){
					return utf8_encode($text);
				}
				else{
					$trunc_length = $include_ending ? $length - strlen($ending) : $length;
					$truncate = mb_substr($text, 0, $trunc_length);
				}
			}
			
			if(!$exact){
				$spacepos = mb_strrpos($truncate, ' ');
				
				if(isset($spacepos)){
					if($consider_html){
						$bits = mb_substr($truncate, $spacepos);
						preg_match_all('/<\/([a-z]+)>/', $bits, $dropped_tags, PREG_SET_ORDER);
						
						if(!empty($dropped_tags)){
							foreach($dropped_tags as $closing_tag){
								if(!in_array($closing_tag[1], $open_tags))
									array_unshift($open_tags, $closing_tag[1]);
							}
						}
					}
					
					$truncate = mb_substr($truncate, 0, $spacepos);
				}
			}
			
			$truncate .= $ending;
			
			if($consider_html){
				foreach($open_tags as $tag)
					$truncate .= '</' . $tag . '>';
			}
			
			$truncate = utf8_encode($truncate);
			
			if($show_title)
				$truncate = '<span title="'.$title_text.'">'.$truncate.'</span>';
			
			return $truncate;
		}
		
		/**
		 * Remove quebras de linha ("\n") de um texto.
		 * 
		 * @param $string Texto a ser transformado.
		 * @return string Texto sem quebras de linha.
		 */
		public static function remove_line_breaks($string){
			return trim(preg_replace('/\s+/', ' ', $string));
		}
		
		/**
		 * Captura o primeiro nome de um nome completo.
		 * 
		 * @param string $name Nome completo.
		 * @return string Primeiro nome.
		 */
		public static function firstname($name){
			return strpos($name, ' ') ? substr($name, 0, strpos($name, ' ')) : $name;
		}
		
		/**
		 * Captura o sobrenome de um nome completo.
		 * 
		 * @param string $name Nome completo.
		 * @return string Sobrenome.
		 */
		public static function lastname($name){
			return strpos($name, ' ') ? substr($name, (strpos($name, ' ') + 1), strlen($name)) : $name;
		}
		
		/**
		 * Monta o nome + último sobrenome (com ou sem abreviações).
		 * 
		 * @param string $name Nome completo.
		 * @param boolean $wrap Define se o nome deve ser envolvido por um elemento SPAN cujo valor do atributo 'title' possua o nome completo sem abreviações.
		 * @param boolean $use_abbreviations Define se o nome deve conter abreviações.
		 * @return string Nome completo abreviado.
		 */
		public static function fullname($name, $wrap = false, $use_abbreviations = true){
			$name_pieces = explode(' ', $name);
			$name_pieces_size = sizeof($name_pieces);
			$original_name = $name;
			$fullname = '';
			
			if($use_abbreviations){
				$no_abbreviations = array('de', 'da', 'do', 'dos', 'das', 'e', 'jr', 'jr.');
				$i = 0;

				foreach($name_pieces as $piece){
					$i++;

					if(!in_array(strtolower($piece), $no_abbreviations)){
						if(($i === 1) || ($i === $name_pieces_size))
							$fullname .= ($name_pieces_size === 2) ? ' '.$piece : $piece;
						else
							$fullname .= ' '.strtoupper(substr($piece, 0, 1)).'. ';
					}
					else{
						$piece = strtolower($piece);
						
						if(in_array($piece, array('jr', 'jr.')))
							$piece = 'Jr.';
						
						$fullname .= ' '.$piece.' ';
					}
				}
			}
			else{
				$fullname = $name_pieces[0].' '.$name_pieces[$name_pieces_size - 1];
			}
			
			if($wrap)
				$fullname = '<span title="'.$original_name.'">'.$fullname.'</span>';
			
			return $fullname;
		}
		
		/**
		 * Transforma quebras de linha HTML (elemento BR) em "\n".
		 * 
		 * @param string $text Texto a ser formatado.
		 * @return string Texto formatado.
		 */
		public static function br2nl($text){
			return preg_replace('/<br(\s+)?\/?>/i', "\n", $text);
		}
		
		/**
		 * Remove todos os caracteres especiais de um texto.
		 * 
		 * @param string $text Texto a ser formatado.
		 * @return Texto sem caracteres especiais.
		 */
		public static function strip_special_chars($text){
			$text = str_replace('&quot;', '"', strtolower(self::remove_accent($text)));
			$striped_text = '';
			$text_length = strlen($text);
			
			for($i = 0; $i < $text_length; $i++){
				$char = substr($text, $i, 1);
				$ascii = ord($char);
				
				if(($ascii < 48 || $ascii > 57) && ($ascii < 97 || $ascii > 122))
					$char = '_';
				
				$striped_text .= $char;
			}
			
			return trim(str_replace('__', '_', str_replace('__', '_', str_replace('___', '_', $striped_text))), '_');
		}
		
		/**
		 * Captura o nome do gênero (sexo).
		 * 
		 * @param int $value Identificador do gênero.
		 * @return string Nome do gênero.
		 */
		public static function gender($value){
			global $sys_language;
			
			switch($value){
				case self::GENDER_MALE:
					$gender = $sys_language->get('class_format', 'male');
					break;
				
				case self::GENDER_FEMALE:
					$gender = $sys_language->get('class_format', 'female');
					break;
				
				default:
					$gender = '';
			}
			
			return $gender;
		}
		
		/**
		 * Retorna a representação literal de um booleano.
		 * 
		 * @param boolean $bool Valor booleano a ser representado.
		 * @return string Representação do valor booleano.
		 */
		public static function bool2string($bool){
			return $bool ? 'true' : 'false';
		}
		
		/**
		 * Comprime uma string removendo comentários, tabulações, espaços e quebras de linha.
		 * 
		 * @param string $data Conteúdo a ser comprimido.
		 * @param string $mode Modo de compressão, que pode ser 'simple' (não remove as quebras de linha) ou 'full'.
		 * @return string Conteúdo comprimido.
		 */
		public static function compress($data, $mode = 'full'){
			if(in_array($mode, array('simple', 'full'))){
				//Remove os comentários
				$data = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $data);
				
				//Remove tabulações, espaços e quebras de linha.
				if($mode == 'full')
					$data = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $data);
				elseif($mode == 'simple')
					$data = str_replace(array("\t", '  ', '    ', '    '), '', $data);
			}
			
			return $data;
		}
		
		/**
		 * Conta uma quantidade no singular ou plural, de acordo com o total.
		 * 
		 * @param int $total Quantidade.
		 * @param string $singular Palavra no singular.
		 * @param string $plural Palavra no plural.
		 * @return string Texto da contagem.
		 */
		public static function count($total, $singular, $plural){
			return ((int)$total === 1) ? '1 '.$singular : (int)$total.' '.$plural;
		}
	}
?>