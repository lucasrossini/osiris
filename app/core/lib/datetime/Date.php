<?php
	namespace DateTime;
	
	/**
	 * Classe com métodos para manipulação de data.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 11/03/2014
	*/
	
	abstract class Date{
		const TYPE_SQL = 0;
		const TYPE_FORMATTED = 1;
		
		const MAX_DAYS_AGO = 3;
		
		/**
		 * Verifica em qual formato está a data ('0000-00-00' ou '00/00/0000').
		 * 
		 * @param string $date Data a ser verificada.
		 * @return int Formato da data.
		 */
		private static function format_type($date){
			return strpos($date, '/') ? self::TYPE_FORMATTED : self::TYPE_SQL;
		}
		
		/**
		 * Converte uma data no formato '00/00/0000' (ou '0000/00/00' caso o sistema esteja em inglês), com ou sem hora, para '0000-00-00' e vice-versa.
		 * 
		 * @param string $date Data a ser formatada.
		 * @return string Data formatada.
		 */
		public static function convert($date){
			$date_pieces = explode(' ', $date);
			$date = $date_pieces[0];
			$time = !empty($date_pieces[1]) ? ' '.$date_pieces[1] : '';
			
			if(\System\Language::get_current_lang() != 'en')
				$return = (self::format_type($date) == self::TYPE_SQL) ? implode('/', array_reverse(explode('-', $date))) : implode('-', array_reverse(explode('/', $date)));
			else
				$return = (self::format_type($date) == self::TYPE_SQL) ? str_replace('-', '/', $date) : str_replace('/', '-', $date);
			
			return $return.$time;
		}
		
		/**
		 * Adiciona dias a uma data.
		 * 
		 * @param string $date Data a ser somada.
		 * @param int $days Quantidade de dias a serem adicionados à data.
		 * @return string Data somada.
		 */
		public static function add($date, $days){
			if($days === 0)
				return $date;
			
			$converted = false;
			
			if(self::format_type($date) == self::TYPE_FORMATTED){
				$date = self::convert($date);
				$converted = true;
			}
			
			$date = gmdate('Y-m-d', strtotime($days.' day', strtotime($date)));
			
			if($converted)
				$date = self::convert($date);
			
			return $date;
		}
		
		/**
		 * Subtrai dias de uma data.
		 * 
		 * @param string $date Data a ser subtraída.
		 * @param int $days Quantidade de dias a serem subtraídos da data.
		 * @return string Data subtraída.
		 */
		public static function subtract($date, $days){
			return self::add($date, -$days);
		}
		
		/**
		 * Calcula a quantidade de dias entre 2 datas.
		 * 
		 * @param string $before_date Data anterior.
		 * @param string $after_date Data posterior.
		 * @param boolean $absolute Define se a quantidade de dias deve ser absoluta, ou seja, sempre positiva.
		 * @return int Quantidade de dias.
		 */
		public static function interval($before_date, $after_date, $absolute = false){
			if(self::format_type($before_date) == self::TYPE_FORMATTED)
				$before_date = self::convert($before_date);
			
			if(self::format_type($after_date) == self::TYPE_FORMATTED)
				$after_date = self::convert($after_date);
			
			$before_date_pieces = explode('-', $before_date);
			$before_date = mktime(0, 0, 0, $before_date_pieces[1], $before_date_pieces[2], $before_date_pieces[0]);
		
			$after_date_pieces = explode('-', $after_date);
			$after_date = mktime(0, 0, 0, $after_date_pieces[1], $after_date_pieces[2], $after_date_pieces[0]);
		
			$days = ceil(($after_date - $before_date) / 86400);
			return $absolute ? abs($days) : $days;
		}
		
		/**
		 * Verifica se uma data está dentro de um intervalo de datas.
		 * 
		 * @param string $date Data a ser verificada.
		 * @param string $before_date Data de início do intervalo.
		 * @param string $after_date Data de término do intervalo.
		 * @return boolean TRUE caso a data esteja no intervalo ou FALSE caso contrário.
		 */
		public static function is_between($date, $before_date, $after_date){
			return ((self::interval($before_date, $date) >= 0) && (self::interval($date, $after_date) >= 0));
		}
		
		/**
		 * Retorna o nome do mês (podendo ser encurtado).
		 * 
		 * @param int $month Número do mês.
		 * @param boolean $short Define se o nome do mês deve ser encurtado (3 primeiros caracteres).
		 * @return string|boolean Nome do mês ou FALSE caso o mês seja inválido.
		 */
		public static function month_name($month, $short = false){
			global $sys_language;
			
			switch((int)$month){
				case 1: $name = $sys_language->get('class_date_format', 'january'); break;
				case 2: $name = $sys_language->get('class_date_format', 'february'); break;
				case 3: $name = $sys_language->get('class_date_format', 'march'); break;
				case 4: $name = $sys_language->get('class_date_format', 'april'); break;
				case 5: $name = $sys_language->get('class_date_format', 'may'); break;
				case 6: $name = $sys_language->get('class_date_format', 'june'); break;
				case 7: $name = $sys_language->get('class_date_format', 'july'); break;
				case 8: $name = $sys_language->get('class_date_format', 'august'); break;
				case 9: $name = $sys_language->get('class_date_format', 'september'); break;
				case 10: $name = $sys_language->get('class_date_format', 'october'); break;
				case 11: $name = $sys_language->get('class_date_format', 'november'); break;
				case 12: $name = $sys_language->get('class_date_format', 'december'); break;
				default: return false;
			}
			
			if($short)
				$name = substr($name, 0, 3);
			
			return $name;
		}
		
		/**
		 * Retorna a data em extenso.
		 * 
		 * @param string $date Data a ser exibida em extenso.
		 * @param boolean $show_year Define se o ano deve ser exibido.
		 * @param boolean $reference_today Define se deve-se utilizar o dia de hoje como referência.
		 * @return string Data em extenso.
		 */
		public static function long_date($date, $show_year = true, $reference_today = false){
			global $sys_language;
			
			if(!$reference_today){
				if(self::format_type($date) == self::TYPE_SQL)
					$date = self::convert($date);
				
				$date_pieces = explode('/', $date);
				$return = $show_year ? str_replace('%y', $date_pieces[2], str_replace('%m', \Formatter\String::strtolower(self::month_name((int)$date_pieces[1])), str_replace('%d', ltrim($date_pieces[0], '0'), $sys_language->get('class_date_format', 'long_date_year')))) : str_replace('%m', \Formatter\String::strtolower(self::month_name((int)$date_pieces[1])), str_replace('%d', ltrim($date_pieces[0], '0'), $sys_language->get('class_date_format', 'long_date')));
			}
			else{
				$days_ago = self::interval(self::convert($date), date('Y-m-d'));
				
				switch($days_ago){
					case 0:
						$return = $sys_language->get('class_date_format', 'today');
						break;
					
					case 1:
						$return = $sys_language->get('class_date_format', 'yesterday');
						break;
					
					default:
						$return = ($days_ago <= self::MAX_DAYS_AGO) ? sprintf($sys_language->get('class_date_format', 'days_ago'), $days_ago) : self::long_date($date, $show_year);
						break;
				}
			}
			
			return $return;
		}
		
		/**
		 * Retorna uma das unidades da data, podendo ser dia da semana, dia, mês ou ano.
		 * 
		 * @param string $date Data a ser processada.
		 * @param string $unit Unidade desejada, podendo ser 'weekday', 'day', 'month' ou 'year'.
		 * @throws Exception Unidade inválida.
		 * @return string Parte da data de acordo com a unidade.
		 */
		public static function get_info($date, $unit){
			global $sys_language;
			
			if(self::format_type($date) == self::TYPE_SQL)
				$date = self::convert($date);
			
			$date_pieces = explode('/', $date);
			
			switch($unit){
				case 'weekday':
					return self::weekday_name(date('w', mktime(0, 0, 0, $date_pieces[1], $date_pieces[0], $date_pieces[2])));
					break;
				
				case 'day':
					return $date_pieces[0];
					break;
				
				case 'month':
					return $date_pieces[1];
					break;
				
				case 'year':
					return $date_pieces[2];
					break;
				
				default:
					throw new \Exception($sys_language->get('class_date_format', 'precision_error'));
					break;
			}
		}
		
		/**
		 * Retorna o nome completo do dia da semana.
		 * 
		 * @param int $w Dia da semana.
		 * @param boolean $short Define se o nome do dia deve ser reduzido (ex.: 'Terça' ao invés de 'Terça-feira').
		 * @return string Nome completo do dia da semana.
		 */
		public static function weekday_name($w, $short = false){
			global $sys_language;
			
			$name = '';
			$short_suffix = $short ? '_short' : '';
			
			switch($w){
				case 0: $weekday = 'sunday'; break;
				case 1: $weekday = 'monday'; break;
				case 2: $weekday = 'tuesday'; break;
				case 3: $weekday = 'wednesday'; break;
				case 4: $weekday = 'thursday'; break;
				case 5: $weekday = 'friday'; break;
				case 6: $weekday = 'saturday'; break;
				default: $weekday = '';
			}
			
			if($weekday)
				$name = $sys_language->get('class_date_format', $weekday.$short_suffix);
				
			return $name;
		}
		
		/**
		 * Calcula a idade a partir de uma data de nascimento.
		 * 
		 * @param string $birth_date Data de nascimento.
		 * @return int Idade calculada.
		 */
		public static function age($birth_date){
			if(self::format_type($birth_date) == self::TYPE_SQL)
				$birth_date = self::convert($birth_date);
			
			$birth_date_pieces = explode('/', $birth_date);
			$current_date_pieces = explode('/', date('d/m/Y'));
			
			$age = $current_date_pieces[2] - $birth_date_pieces[2];
			
			if(($current_date_pieces[1] < $birth_date_pieces[1]) || (($current_date_pieces[1] == $birth_date_pieces[1]) && ($current_date_pieces[0] < $birth_date_pieces[0])))
				$age--;
			
			return $age;
		}
		
		/*-- Data atual --*/
		
		/**
		 * Retorna o dia da semana atual.
		 * 
		 * @return string Dia da semana atual.
		 */
		public static function get_current_weekday(){
			return self::weekday_name(date('w'));
		}
		
		/**
		 * Retorna o nome do mês atual.
		 * 
		 * @return string Nome do mês atual.
		 */
		public static function get_current_month(){
			return self::month_name(date('n'));
		}
		
		/**
		 * Retorna o dia atual.
		 * 
		 * @return string Dia atual.
		 */
		public static function get_current_day(){
			return (date('d') == '01') ? '1º' : date('d');
		}
		
		/**
		 * Retorna a data por extenso atual.
		 * 
		 * @return string Data por extenso atual.
		 */
		public static function get_current_long_date(){
			global $sys_language;
			return str_replace('%S', date('S'), str_replace('%j', date('j'), str_replace('%y', date('Y'), str_replace('%m', \Formatter\String::strtolower(self::get_current_month()), str_replace('%d', self::get_current_day(), str_replace('%w', self::get_current_weekday(), $sys_language->get('class_date_format', 'current_long_date')))))));
		}
	}
?>