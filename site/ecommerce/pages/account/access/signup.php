<section id="signup">
	<h1>Cadastro</h1>
	
	<?php
		$user_address_table = DAO\Ecommerce\Address::TABLE_NAME;
		
		//Campos
		$password_field = new \Form\Password('password', 'Senha');
		
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
			),
			'password_confirm' => array(
				'validation' => array(
					'is_empty',
					'compare' => array('type' => 'equal', 'with' => $password_field)
				)
			),
			'signup_date' => array(
				'save' => true,
				'type' => 'curdate'
			),
			'signup_time' => array(
				'save' => true,
				'type' => 'curtime'
			),
			'title' => array(
				'save' => true,
				'value' => 'Casa',
				'related' => true,
				'table' => $user_address_table
			),
			'zip_code' => array(
				'save' => true,
				'related' => true,
				'table' => $user_address_table,
				'validation' => array('is_empty')
			),
			'street' => array(
				'save' => true,
				'related' => true,
				'table' => $user_address_table,
				'validation' => array('is_empty')
			),
			'number' => array(
				'save' => true,
				'related' => true,
				'table' => $user_address_table,
				'validation' => array('is_empty', 'is_number')
			),
			'complement' => array(
				'save' => true,
				'related' => true,
				'table' => $user_address_table
			),
			'neighborhood' => array(
				'save' => true,
				'related' => true,
				'table' => $user_address_table,
				'validation' => array('is_empty')
			),
			'state_id' => array(
				'save' => true,
				'related' => true,
				'table' => $user_address_table,
				'validation' => array('is_empty', 'is_valid_option')
			),
			'city_id' => array(
				'save' => true,
				'related' => true,
				'table' => $user_address_table,
				'validation' => array('is_empty')
			),
			'default' => array(
				'save' => true,
				'value' => 1,
				'related' => true,
				'table' => $user_address_table
			)
		);
		
		//Relacionamentos
		$relationships = array(
			$user_address_table => array(
				'foreign_key' => 'client_id'
			)
		);

		//Cria formulário
		$form = new \Form\Form('form_signup');
		$form->set_mode('insert');
		$form->set_database_options(DAO\Ecommerce\Client::TABLE_NAME, $fields, null, $relationships);
		
		$form->add_html('<h2>Dados pessoais</h2>');
		
		$form->add_html('<div class="inline-labels grid-3">');
		$form->add_field(new \Form\TextInput('name', 'Nome'));
		$form->add_field(new \Form\TextInput('cpf', 'CPF', '', array(), 'cpf'));
		$form->add_field(new \Form\TextInput('phone', 'Telefone de contato', '', array(), 'phone'));
		$form->add_html('</div>');

		$form->add_html('<div class="inline-labels grid-3">');
		$form->add_field(new \Form\TextInput('email', 'E-mail', '', array(), 'email'));
		$form->add_field($password_field);
		$form->add_field(new \Form\Password('password_confirm', 'Confirmar senha', '', array(), false));
		$form->add_html('</div>');
		
		$form->add_html('<h2>Endereço</h2>');
		
		$form->add_field(new \Form\TextInput('zip_code', 'CEP', '', array(), 'cep'));
		
		$form->add_html('<div class="inline-labels grid-3">');
		$form->add_field(new \Form\TextInput('street', 'Rua'));
		$form->add_field(new \Form\Number('number', 'Número'));
		$form->add_field(new \Form\TextInput('complement', 'Complemento'));
		$form->add_html('</div>');
		
		$form->add_html('<div class="inline-labels grid-3">');
		$form->add_field(new \Form\TextInput('neighborhood', 'Bairro'));
		$form->add_field(new \Form\Select('state_id', 'Estado', '', array(), Form\Select::load_options('sys_state', '[name]', 'TRUE', 'name')));
		$form->add_field(new \Form\Select('city_id', 'Cidade', '', array(), array('' => 'Selecione um estado')));
		$form->add_html('</div>');

		$form->add_html('<div class="button-container">');
		$form->add_field(new \Form\Button('submit_button'));
		$form->add_html('</div>');

		//Valida o formulário
		$echo_validation = \HTTP\Request::post('suppress_errors') ? false : true;
		$form->validate($echo_validation);

		//Detecta alterações no formulário
		$form->detect_changes();

		//Exibe o formulário
		$form->display();

		//Trata formulário após o envio
		$messages = array(
			'insert' => array(
				'success' => 'Seu cadastro foi realizado com sucesso! Obrigado.',
				'error' => 'Falha ao realizar cadastro! Tente novamente.',
			)
		);
		
		if($form->is_success()){
			if($form->process(false, array(), true, $messages)){
				//Limpa endereço selecionado no carrinho
				$cart = new DAO\Ecommerce\Cart();
				$cart->select_address(null);
				
				//Limpa o log de produtos visualizados
				\HTTP\Session::delete(DAO\Ecommerce\Product::VIEW_SESSION);
				
				//Insere nome do usuário como nome do destinatário do endereço
				$db->query('UPDATE ecom_address SET addressee = "'.$form->get('name').'" WHERE client_id = '.$form->get_process_id());
				
				//Efetua login
				$next_url = \HTTP\Request::get('next') ? \HTTP\Request::get('next') : '/minha-conta';
				$sys_user->login(array('login' => array('field' => array('email'), 'value' => $form->get('email')), 'password' => array('field' => 'password', 'value' => $form->get('password'))), $next_url, true, true);
			}
			
			\URL\URL::reload();
		}
	?>
</section>

<script>
	var last_zip_code = '';
	
	$(document).ready(function(){
		//Carrega as cidades do estado
		$('#state_id').change(function(){
			Ajax.load_select_options({a: 1, id: $(this).val()}, $('#city_id'));
		});
		
		//Carrega o endereço do CEP
		$('#zip_code').blur(function(){
			if(($(this).val() != last_zip_code) && ($(this).val().length == 9)){
				Ajax.load_zip_address($(this).val(), $('#street'), $('#neighborhood'), $('#state_id'), $('#city_id'));
				$('#number').focus();
			}
			
			last_zip_code = $(this).val();
		});
	});
	
	$(window).load(function(){
		//Carrega o endereço do CEP já preenchido
		$('#zip_code').blur();
		
		//Dá foco no campo de nome
		$('#name').focus();
	});
</script>