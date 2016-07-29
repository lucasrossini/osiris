<link rel="stylesheet" href="/site/ecommerce/assets/css/account.css" />

<?php
	//Cabeçalho da conta
	include 'ecommerce/inc/account-header.php';
	
	//Menu da conta
	include 'ecommerce/inc/account-menu.php';
?>

<section id="edit-data">
	<h1>Editar meus dados</h1>
	
	<?php
		$client_id = $sys_user->get('id');
		
		//Campos
		$fields = array(
			'name' => array(
				'save' => true,
				'validation' => array('is_empty')
			),
			'cpf' => array(
				'save' => true,
				'validation' => array('is_empty', 'is_cpf', 'already_exists')
			),
			'phone' => array(
				'save' => true,
				'validation' => array('is_empty')
			),
			'email' => array(
				'save' => true,
				'validation' => array('is_empty', 'is_email', 'already_exists')
			),
			'password' => array(
				'save' => true,
				'type' => 'password',
				'validation' => array('is_empty')
			)
		);

		//Cria formulário
		$form = new \Form\Form('form_edit_data');
		$form->set_mode('edit', false);
		$form->set_database_options(DAO\Ecommerce\Client::TABLE_NAME, $fields, $client_id);
		
		$form->add_html('<div class="inline-labels grid-3">');
		$form->add_field(new \Form\TextInput('name', 'Nome'));
		$form->add_field(new \Form\TextInput('cpf', 'CPF', '', array(), 'cpf'));
		$form->add_field(new \Form\TextInput('phone', 'Telefone de contato', '', array(), 'phone'));
		$form->add_html('</div>');

		$form->add_html('<div class="inline-labels">');
		$form->add_field(new \Form\TextInput('email', 'E-mail', '', array(), 'email'));
		$form->add_field(new \Form\Password('password', 'Senha'));
		$form->add_html('</div>');

		$form->add_html('<div class="button-container">');
		$form->add_field(new \Form\Button('submit_button'));
		$form->add_field(new \Form\Button('cancel_button', $sys_language->get('common', 'cancel'), '', array(), 'button'));
		$form->add_html('</div>');

		//Valida o formulário
		$form->validate();

		//Detecta alterações no formulário
		$form->detect_changes();

		//Exibe o formulário
		$form->display();

		//Trata formulário após o envio
		$messages = array(
			'edit' => array(
				'success' => 'Seus dados foram atualizados com sucesso!',
				'error' => 'Falha ao atualizar seus dados! Tente novamente.',
			)
		);
		
		$form->process(true, array(), true, $messages);
	?>
</section>

<script>
	//Cancelar
	$('#cancel_button').click(function(){
		window.location.href = '/minha-conta';
	});
</script>