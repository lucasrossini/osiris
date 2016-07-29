<?php
	namespace DateTime;
	
	/**
	 * Classe com métodos para manipulação de hora.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 24/01/2014
	*/
	
	abstract class Time{
		/**
		 * Converte uma hora no formato '00:00' para '00:00:00'.
		 * 
		 * @param string $time Hora a ser formatada.
		 * @return string Hora formatada.
		 */
		public static function time2sql($time){
			return $time.':00';
		}
		
		/**
		 * Converte uma hora no formato '00:00:00' para '00:00'.
		 * 
		 * @param string $time Hora a ser formatada.
		 * @return string Hora formatada.
		 */
		public static function sql2time($time){
			$time_pieces = explode(':', $time);
			return $time_pieces[0].':'.$time_pieces[1];
		}
		
		/**
		 * Calcula a diferença de tempo entre dois horários.
		 * 
		 * @param string $before_time Hora anterior.
		 * @param string $after_time Hora posterior.
		 * @param string $precision Precisão da diferença, podendo ser em dias, horas, minutos ou segundos.
		 * @param boolean $absolute Define se a diferença de tempo deve ser absoluta, ou seja, sempre positiva.
		 * @throws Exception Precisão inválida.
		 * @return int Diferença de tempo.
		 */
		public static function interval($before_time, $after_time, $precision = 'minutes', $absolute = false){
			global $sys_language;
			
			$before_time_pieces = explode(':', $before_time);
			$before_time = mktime((int)$before_time_pieces[0], (int)$before_time_pieces[1], (int)$before_time_pieces[2], date('m'), date('d'), date('Y'));
			
			$after_time_pieces = explode(':', $after_time);
			$after_time = mktime((int)$after_time_pieces[0], (int)$after_time_pieces[1], (int)$after_time_pieces[2], date('m'), date('d'), date('Y'));
			
			$negative = false;
			
			if($before_time > $after_time){
				$aux = $before_time;
				$before_time = $after_time;
				$after_time = $aux;
				$negative = true;
			}
			
			$timestamp = ($after_time - $before_time);
			$time = gmdate('H:i:s', $timestamp);
			
			switch(strtolower($precision)){
				case 'time':
					$diff = $time;
					break;
				
				case 'days':
				case 'hours':
				case 'minutes':
				case 'seconds':
					$diff = self::length($time, $precision);
					break;
				
				default:
					throw new \Exception($sys_language->get('class_date_format', 'precision_error'));
					break;
			}
			
			return (!$negative || $absolute) ? $diff : -$diff;
		}
		
		/**
		 * Calcula o total de dias, horas, minutos ou segundos de um determinado horário em relação ao próprio dia.
		 * 
		 * @param string $time Hora a ser verificada.
		 * @param string $precision Precisão para o cálculo, podendo ser em dias, horas, minutos ou segundos.
		 * @param boolean $format Define se o valor calculado deve ser formatado no padrão '0,0'.
		 * @throws Exception Precisão inválida.
		 * @return int Total de acordo com a precisão.
		 */
		public static function length($time, $precision = 'minutes', $format = false){
			global $sys_language;
			
			$time_pieces = explode(':', $time);
			$time_pieces[2] = (int)$time_pieces[2];
			
			switch(strtolower($precision)){
				case 'days':
					$return = (($time_pieces[0] / 24) + ($time_pieces[1] / 60 / 24) + ($time_pieces[2] / 60 / 60 / 24));
					break;
				
				case 'hours':
					$return = ($time_pieces[0] + ($time_pieces[1] / 60) + ($time_pieces[2] / 60 / 60));
					break;
				
				case 'minutes':
					$return = (($time_pieces[0] * 60) + $time_pieces[1] + ($time_pieces[2] / 60));
					break;
				
				case 'seconds':
					$return = ($time_pieces[0] * 60 * 60) + ($time_pieces[1] * 60) + $time_pieces[2];
					break;
				
				default:
					throw new \Exception($sys_language->get('class_date_format', 'precision_error'));
					break;
			}
			
			return ($format && (strtolower($precision) != 'seconds')) ? number_format($return, 1, ',', '.') : $return;
		}
		
		/**
		 * Calcula o tempo em horas, minutos e segundos de uma determinada quantidade de segundos.
		 * 
		 * @param int $seconds Quantidade de segundos.
		 * @return array Vetor com os índices 'hours', 'minutes' e 'seconds'.
		 */
		public static function seconds2time($seconds){
			//Horas
			$hours = floor($seconds / 3600);
			
			//Minutos
			$minutes = floor($seconds / 60) - ($hours * 60);
			
			//Segundos
			$seconds = $seconds % 60;
			
			return array('hours' => $hours, 'minutes' => $minutes, 'seconds' => $seconds);
		}
	}
?>