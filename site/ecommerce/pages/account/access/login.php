<link rel="stylesheet" href="/site/ecommerce/assets/css/login.css" />

<?php
	//Redireciona para a conta se o usuário já estiver logado
	if($sys_user->is_logged())
		\URL\URL::redirect('/minha-conta');
?>

<section id="login">
	<h1>Entrar</h1>
	
	<div id="login-box" class="box">
		<h2>Já sou cliente</h2>

		<?php
			//Cria formulário
			$form_login = new \Form\Form('form_login');

			//Cria os campos do formulário
			$form_login->add_field(new \Form\TextInput('email', 'E-mail', '', array(), 'email'));
			$form_login->add_field(new \Form\Password('password', 'Senha', '', array(), false));
			$form_login->add_html('<a href="/recuperar-senha" class="recover-password">Esqueci minha senha</a>');
			$form_login->add_field(new \Form\Checkbox('remember_data', 'Lembrar meus dados', 1));

			$form_login->add_html('<div class="button-container">');
			$form_login->add_field(new \Form\Button('login_button', 'Entrar'));
			$form_login->add_html('</div>');

			if($form_login->is_success()){
				$next_url = \HTTP\Request::get('next') ? \HTTP\Request::get('next') : '/minha-conta';
				$remember_data = $form_login->get('remember_data') ? true : false;
				
				//Limpa endereço selecionado no carrinho
				$cart = new DAO\Ecommerce\Cart();
				$cart->select_address(null);
				
				//Limpa o log de produtos visualizados
				\HTTP\Session::delete(DAO\Ecommerce\Product::VIEW_SESSION);
				
				//Efetua login
				$sys_user->login(array('login' => array('field' => array('email'), 'value' => $form_login->get('email')), 'password' => array('field' => 'password', 'value' => $form_login->get('password'))), $next_url, true, $remember_data);
			}

			//Exibe o formulário
			$form_login->display(true, false);
		?>
	</div>

	<div id="signup-box" class="box">
		<h2>Ainda não sou cliente</h2>

		<?php
			//Cria formulário
			$form_action = \HTTP\Request::get('next') ? '/cadastro?next='.\HTTP\Request::get('next') : '/cadastro';
			$form_signup = new \Form\Form('form_signup', 'post', array('action' => $form_action));

			//Cria os campos do formulário
			$form_signup->add_field(new \Form\TextInput('zip_code', 'CEP', '', array(), 'cep'));
			$form_signup->add_field(new \Form\Hidden('suppress_errors', '', 1));
			$form_signup->add_html('<a href="http://www.buscacep.correios.com.br" target="_blank" class="find-cep">Não sei meu CEP</a>');

			$form_signup->add_html('<div class="button-container">');
			$form_signup->add_field(new \Form\Button('signup_button', 'Continuar'));
			$form_signup->add_html('</div>');

			//Exibe o formulário
			$form_signup->display(true, false);
		?>
	</div>
</section>