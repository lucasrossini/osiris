<section id="recover-password">
	<h1>Recuperar senha</h1>
	
	<?php
		//Cria formulário
		$form = new \Form\Form('form_recover_password');
		
		//Cria os campos do formulário
		$form->add_field(new \Form\TextInput('email', 'E-mail', '', array(), 'email'));
		
		$form->add_html('<div class="button-container">');
		$form->add_field(new \Form\Button('recover_button', 'Enviar'));
		$form->add_html('</div>');
		
		if($form->is_success()){
			$email = strtolower($form->get('email'));
			$db->query('SELECT name, password FROM '.DAO\Ecommerce\Client::TABLE_NAME.' WHERE email = "'.$email.'"');
			
			if($db->row_count()){
				$data = $db->result(0);
				
				$message = '
					Prezado '.Formatter\String::firstname($data->name).',<br />
					seguem abaixo seus dados de acesso à nossa loja virtual:<br /><br />

					<strong>E-mail:</strong> '.$email.'<br />
					<strong>Senha:</strong> '.\Security\Crypt::undo($data->password).'
				';
				
				$mail = new \Mail\Email(array('name' => TITLE, 'email' => MAIL_USER), array('name' => TITLE, 'email' => $email), 'Recuperação dos dados de acesso', $message);
				$mail->send(true, array('success' => 'Seus dados de acesso foram enviados para o e-mail '.$email.' com sucesso!<br />Caso não receba o e-mail, lembre-se de verificar sua caixa de <em>spam</em>.', 'error' => 'Falha ao enviar dados! Tente novamente.'));
			}
			else{
				\UI\Message::error('Não foi encontrado nenhum cliente cadastrado com o e-mail informado! Tente novamente.');
			}
			
			\URL\URL::reload();
		}
		
		//Exibe o formulário
		$form->display(true, false);
	?>
</section>