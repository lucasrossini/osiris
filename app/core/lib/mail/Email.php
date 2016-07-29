<?php
	namespace Mail;
	
	/**
	 * Classe para envio de e-mails.
	 * 
	 * @package Osiris
	 * @uses PHPMailer
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 13/03/2014
	*/
	
	class Email{
		private $mail;
		private $from = array();
		private $to = array();
		private $subject;
		private $message;
		private $is_html;
		private $html;
		
		const HOST = MAIL_HOST;
		const USER = MAIL_USER;
		const PASS = MAIL_PASS;
		
		const HEADER_IMAGE = '/site/media/images/email/header.jpg';
		const LOGO_IMAGE = '/site/media/images/email/logo.png';
		
		/**
		 * Cria um objeto de envio de e-mails.
		 * 
		 * @param array $from Vetor com os índices 'email', que indica o endereço de e-mail do remetente; e 'name', que indica o nome do remetente.
		 * @param array $to Vetor com os índices 'email', que indica o endereço de e-mail do destinatário; e 'name', que indica o nome do destinatário.
		 * @param string $subject Assunto do e-mail.
		 * @param string $message Conteúdo do e-mail.
		 * @param boolean $ssl Define se o host de envio de e-mails utiliza SSL.
		 * @param int $port Porta do host de envio de e-mails.
		 * @param boolean $is_html Define se o e-mail será enviado em formato HTML.
		 * @param boolean $use_default_html Define se a mensagem de e-mail utilizará o corpo padrão do sistema.
		 */
		public function __construct($from = array(), $to = array(), $subject = '', $message = '', $ssl = false, $port = '', $is_html = true, $use_default_html = true){
			global $sys_language;
			
			//Inclui a biblioteca do PHPMailer
			require_once CORE_PATH.'/lib/mail/phpmailer/class.phpmailer.php';
			
			//Insere assunto padrão caso ele não seja definido
			if(empty($subject))
				$subject = $sys_language->get('class_email', 'no_subject');
			
			$this->from = $from;
			$this->to = $to;
			$this->subject = utf8_decode($subject);
			$this->message = utf8_decode($message);
			$this->is_html = $is_html;
			$this->html = '';
			
			//Objeto PHPMailer
			$this->mail = new \PHPMailer();
			$this->mail->SetLanguage(\System\Language::get_current_lang());
			$this->mail->IsSMTP();
			$this->mail->SMTPAuth = true;
			$this->mail->Mailer = 'smtp';
			
			if($ssl)
				$this->mail->SMTPSecure = 'ssl';
			
			if(!empty($port))
				$this->mail->Port = $port;
			
			//Autenticação
			$this->mail->Host = self::HOST;
			$this->mail->Username = self::USER;
			$this->mail->Password = self::PASS;
			
			//Remetente
			$this->mail->From = $this->mail->Username;
			$this->mail->FromName = $this->from['name'];
			
			//Mensagem
			$this->mail->Subject = $this->subject;
			
			if($use_default_html)
				$this->prepare_body();
			else
				$this->html = $this->message;
			
			$this->mail->IsHTML($this->is_html);
			$this->mail->AltBody = $this->message;
			$this->mail->WordWrap = 50;
			
			$this->mail->Body = $this->html;
			
			//Destinatário
			$this->mail->AddAddress($this->to['email'], utf8_decode($this->to['name']));
			$this->mail->AddReplyTo($this->from['email'], utf8_decode($this->from['name']));
			$this->mail->SetFrom($this->from['email'], utf8_decode($this->from['name']));
		}
		
		/**
		 * Prepara o corpo padrão do e-mail.
		 */
		private function prepare_body(){
			global $sys_language;
			
			if($this->is_html){
				$logo_image_obj = new \Media\Image(self::LOGO_IMAGE);
				
				$this->html .= '
					<table summary="'.utf8_decode($sys_language->get('class_email', 'body')).'" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#EFEFEF" style="background: #EFEFEF">
						<tbody>
							<tr><td height="30"></td></tr>
							
							<tr>
								<td>
									<table cellpadding="0" cellspacing="0" border="0" align="center" width="700">
										<tbody>
											<tr>
												<td>
													<table summary="'.utf8_decode($sys_language->get('class_email', 'header')).'" cellpadding="0" cellspacing="0" border="0" align="center" width="650">
														<tbody>
															<tr>
																<td>
																	<a href="'.BASE.'"><img src="'.BASE.self::LOGO_IMAGE.'" width="'.$logo_image_obj->get_width().'" height="'.$logo_image_obj->get_height().'" alt="'.utf8_decode(TITLE).'" border="0" style="display: block" /></a>
																</td>
															</tr>

															<tr><td height="15"></td></tr>
														</tbody>
													</table>

													<table summary="'.utf8_decode($sys_language->get('class_email', 'content')).'" cellpadding="0" cellspacing="0" border="0" align="center" width="650" bgcolor="#FFFFFF" style="border: solid 1px #DDD; background: #FFF">
														<tbody>
															<tr><td height="30"></td></tr>

															<tr>
																<td>
																	<table align="center" border="0" cellpadding="0" cellspacing="0" width="580">
																		<tbody>
																			<tr><td>'.$this->message.'</td></tr>
																		</tbody>
																	</table>
																</td>
															</tr>

															<tr><td height="30"></td></tr>
														</tbody>
													</table>

													<table summary="'.utf8_decode($sys_language->get('class_email', 'footer')).'" cellpadding="0" cellspacing="0" border="0" align="center" width="650">
														<tbody>
															<tr><td height="15"></td></tr>

															<tr>
																<td style="color: #999; font-size: 11px">
																	'.utf8_decode(sprintf($sys_language->get('class_email', 'email_sent_to'), '<a href="mailto:'.$this->to['email'].'" style="color: #999">'.$this->to['email'].'</a>')).'<br />
																	'.utf8_decode($sys_language->get('class_email', 'no_reply')).'<br />
																	<a href="'.BASE.'" style="color: #999">'.BASE.'</a><br />
																	'.utf8_decode(TITLE).', '.date('Y').'
																</td>
															</tr>
														</tbody>
													</table>
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
							
							<tr><td height="30"></td></tr>
						</tbody>
					</table>
				';
			}
			else{
				$this->html = $this->message;
			}
		}
		
		/**
		 * Adiciona um endereço de destino.
		 * 
		 * @param string $email Endereço de e-mail do destinatário.
		 * @param string $name Nome do destinatário.
		 */
		public function add_address($email, $name = ''){
			$this->mail->AddAddress($email, utf8_decode($name));
		}
		
		/**
		 * Anexa um arquivo ao e-mail.
		 * 
		 * @param string $path Pasta onde o arquivo está localizado.
		 * @param string $file Nome do arquivo a ser anexado.
		 * @param string $filename Nome do anexo (se não preenchido, mantém o nome original).
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function add_attachment($path, $file, $filename = ''){
			\Storage\Folder::fix_path($path);
			
			if(\Storage\File::exists($path.$file)){
				$file = ROOT.$path.$file;
				
				if(!empty($filename))
					$this->mail->AddAttachment($file, utf8_decode($filename));
				else
					$this->mail->AddAttachment($file);
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Envia o e-mail.
		 * 
		 * @param boolean $show_message Define se a mensagem de sucesso/erro deve ser exibida após o envio do e-mail. 
		 * @param array $messages Vetor com os índices 'success', que indica a mensagem de sucesso no envio do e-mail; e 'error', que indica a mensagem de erro no envio do e-mail.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function send($show_message = true, $messages = array('success' => '', 'error' => '')){
			global $sys_language;
			
			if(empty($messages['success']))
				$messages['success'] = $sys_language->get('class_email', 'send_success');
			if(empty($messages['error']))
				$messages['error'] = $sys_language->get('class_email', 'send_error');
			
			if(!\HTTP\Server::is_local() && !$this->mail->Send()){
				$error = $messages['error'].'<br /><strong>Erro:</strong> '.$this->mail->ErrorInfo;
				
				if($show_message)
					\UI\Message::error($error);
			}
			else{
				if($show_message)
					\UI\Message::success($messages['success']);
				
				return true;
			}
			
			return false;
		}
	}
?>