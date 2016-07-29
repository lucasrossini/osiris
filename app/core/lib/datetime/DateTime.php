<?php
	namespace DateTime;
	
	/**
	 * Classe com métodos para manipulação de data + hora.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 24/01/2014
	*/
	
	abstract class DateTime{
		/**
		 * Converte uma data + hora para o formato RFC 2822.
		 * 
		 * @param string $date Data.
		 * @param string $time Hora.
		 * @return string Data + hora formatada.
		 */
		public static function rfc2822($date, $time = '00:00:00'){
			if(self::format_type($date) == 'date')
				$date = Date::convert($date);
			
			list($y, $m, $d) = explode('-', $date);
			list($h, $i, $s) = explode(':', $time);

			return date('r', mktime((int)$h, (int)$i, (int)$s, (int)$m, (int)$d, (int)$y));
		}
		
		/**
		 * Calcula a diferença de tempo entre dois datetimes.
		 * 
		 * @param string $before_datetime Datetime anterior.
		 * @param string $after_datetime Datetime posterior.
		 * @param string $precision Precisão da diferença, podendo ser em dias, horas, minutos ou segundos.
		 * @throws Exception Precisão inválida.
		 * @return int Diferença de tempo.
		 */
		public static function interval($before_datetime, $after_datetime, $precision = 'minutes'){
			global $sys_language;
			
			$before_datetime_pieces = explode(' ', $before_datetime);
			$after_datetime_pieces = explode(' ', $after_datetime);
			
			$days = Date::interval($before_datetime_pieces[0], $after_datetime_pieces[0]);
			
			switch(strtolower($precision)){
				case 'days':
					$return = $days;
					break;
				
				case 'hours':
					$return = ($days * 24);
					break;
				
				case 'minutes':
					$return = ($days * 24 * 60);
					break;
				
				case 'seconds':
					$return = ($days * 24 * 60 * 60);
					break;
				
				default:
					throw new \Exception($sys_language->get('class_date_format', 'precision_error'));
					break;
			}
			
			return $return + Time::interval($before_datetime_pieces[1], $after_datetime_pieces[1], $precision);
		}
		
		/**
		 * Retorna a data e hora em relação à data/hora atual.
		 * 
		 * @param string $date Data.
		 * @param string $time Hora.
		 * @return string Data/hora em relação à data/hora atual.
		 */
		public static function reference_now($date, $time){
			global $sys_language;
			$time_ago = '';
			
			if(self::format_type($date) != 'sql')
				$date = Date::convert($date);
			
			if((int)Date::interval($date, date('Y-m-d')) === 0){
				if(Time::interval($time, date('H:i:s'), 'minutes') >= 60){
					$hours_ago = round(Time::interval($time, date('H:i:s'), 'hours'));
					$s = ($hours_ago > 1) ? 's' : '';
					$time_ago = sprintf($sys_language->get('class_date_format', 'hours_ago'), $hours_ago, $s);
				}
				else{
					$minutes_ago = round(Time::interval($time, date('H:i:s'), 'minutes'));
					$s = ($minutes_ago > 1) ? 's' : '';
					$time_ago = $minutes_ago ? sprintf($sys_language->get('class_date_format', 'minutes_ago'), $minutes_ago, $s) : $sys_language->get('class_date_format', 'few_seconds');
				}
			}
			else{
				$date_pieces = explode('-', $date);
				$show_year = ($date_pieces[0] < date('Y'));
				$on = (Date::interval(Date::convert($date), date('Y-m-d')) > self::MAX_DAYS_AGO) ? $sys_language->get('class_date_format', 'date_on').' ' : '';
				$time_ago = $on.Date::long_date($date, $show_year, true);
			}
			
			return '<span title="'.Date::get_info($date, 'weekday').', '.Date::long_date($date).', '.$sys_language->get('class_date_format', 'date_at').' '.Time::sql2time($time).'h">'.$time_ago.'</span>';
		}
		
		/**
		 * Retorna uma das unidades do datetime.
		 * 
		 * @param string $datetime Datetime a ser processado.
		 * @param string $unit Unidade desejada, podendo ser 'date' ou 'time'.
		 * @param boolean $convert Define se a unidade capturada deve ser formatada no padrão '00/00/0000' para data e '00:00' para hora.
		 * @throws Exception Unidade inválida.
		 * @return string Parte do datetime de acordo com a unidade.
		 */
		public static function get_info($datetime, $unit = 'date', $convert = true){
			global $sys_language;
			$datetime_pieces = explode(' ', $datetime);
			
			switch($unit){
				case 'date':
					return $convert ? Date::convert($datetime_pieces[0]) : $datetime_pieces[0];
					break;
				
				case 'time':
					return $convert ? Time::sql2time($datetime_pieces[1]) : $datetime_pieces[1];
					break;
				
				default:
					throw new \Exception($sys_language->get('class_date_format', 'precision_error'));
					break;
			}
		}
		
		/**
		 * Retorna o datetime atual.
		 * 
		 * @return string Datetime atual.
		 */
		public static function now(){
			return date('Y-m-d').' '.date('H:i:s');
		}
	}
?>