<?php
	namespace UI;
	
	/**
	 * Classe que manipula as mensagens do sistema.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 07/04/2013
	*/
	
	abstract class Message{
		const SUCCESS = 'success_msg',
			  ERROR = 'error_msg',
			  INFO = 'info_msg',
			  PERMISSION_ERROR = 'permission_error_msg';
		
		/**
		 * Define uma nova mensagem de sucesso.
		 * 
		 * @param string $message Mensagem a ser exibida.
		 */
		public static function success($message){
			\HTTP\Session::create(self::SUCCESS, $message);
		}
		
		/**
		 * Define uma nova mensagem de erro.
		 * 
		 * @param string $message Mensagem a ser exibida.
		 */
		public static function error($message){
			\HTTP\Session::create(self::ERROR, $message);
		}
		
		/**
		 * Define uma nova mensagem de informação.
		 * 
		 * @param string $message Mensagem a ser exibida.
		 */
		public static function info($message){
			\HTTP\Session::create(self::INFO, $message);
		}
		
		/**
		 * Define uma nova mensagem de erro de permissão.
		 * 
		 * @param string $message Mensagem a ser exibida.
		 */
		public static function permission_error($message){
			\HTTP\Session::create(self::PERMISSION_ERROR, $message);
		}
		
		/**
		 * Captura o texto de uma mensagem pendente.
		 * 
		 * @param string $type Tipo de mensagem a ser capturada.
		 * @param boolean $delete_after Define se a mensagem deve ser apagada após sua captura.
		 * @return string Texto da mensagem.
		 */
		public static function get_message($type, $delete_after = true){
			$reflection_class = new \ReflectionClass(get_class());
			$type = $reflection_class->getConstant(strtoupper($type));
			
			$message = \HTTP\Session::get($type);
			
			if($delete_after)
				\HTTP\Session::delete($type);
			
			return $message;
		}
		
		/**
		 * Exibe um tipo de mensagem pendente de exibição.
		 * 
		 * @param string $type Tipo de mensagem a ser exibida.
		 * @param boolean $show_close_button Define se deve ser exibido um botão para fechar a mensagem.
		 * @param boolean $delete_after Define se a mensagem deve ser apagada após sua captura.
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public static function show_message($type, $show_close_button = true, $delete_after = true, $echo = true){
			global $sys_language;
			
			if($show_close_button){
				$close_button = '
					<a href="#" title="'.$sys_language->get('class_message', 'close').'" class="close">'.$sys_language->get('class_message', 'close').'</a>
					
					<script>
						//Fecha a mensagem
						$(".form-box > .close").click(function(){
							var container = $(this).parent();
							
							container.fadeTo(400, 0, function(){
								container.slideUp(600, function(){
									container.remove();
								});
							});
							
							return false;
						});
					</script>
				';
			}
			
			switch($type){
				case 'success': //Mensagem de sucesso
					if(\HTTP\Session::get(self::SUCCESS)){
						$message = '<div class="form-box success">'.$close_button.\HTTP\Session::get(self::SUCCESS).'</div>';
						
						if($delete_after)
							\HTTP\Session::delete(self::SUCCESS);
					}
					
					break;
				
				case 'error': //Mensagem de erro
					if(\HTTP\Session::get(self::ERROR)){
						$message = '<div class="form-box error">'.$close_button.\HTTP\Session::get(self::ERROR).'</div>';
						
						if($delete_after)
							\HTTP\Session::delete(self::ERROR);
					}
					
					break;
				
				case 'info': //Mensagem de informação
					if(\HTTP\Session::get(self::INFO)){
						$message = '<div class="form-box info">'.$close_button.\HTTP\Session::get(self::INFO).'</div>';
						
						if($delete_after)
							\HTTP\Session::delete(self::INFO);
					}
					
					break;
				
				case 'permission_error': //Mensagem de erro de permissão
					if(\HTTP\Session::get(self::PERMISSION_ERROR)){
						$message = \System\System::permission_error_message(\HTTP\Session::get(self::PERMISSION_ERROR));
						
						if($delete_after)
							\HTTP\Session::delete(self::PERMISSION_ERROR);
					}
					
					break;
			}
			
			if($echo)
				echo $message;
			else
				return $message;
		}
		
		/**
		 * Exibe as mensagens pendentes de exibição.
		 * 
		 * @param boolean $show_close_button Define se deve ser exibido um botão para fechar a mensagem.
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public static function show_messages($show_close_button = true, $echo = true){
			$messages = self::show_message('success', $show_close_button, true, false);
			$messages .= self::show_message('error', $show_close_button, true, false);
			$messages .= self::show_message('info', $show_close_button, true, false);
			$messages .= self::show_message('permission_error', $show_close_button, true, false);
			
			if($echo)
				echo $messages;
			else
				return $messages;
		}
	}
?>