<div id="login-box">
	<?php
		//Redireciona para a página inicial se o usuário já estiver logado
		if($sys_user->is_logged())
			\URL\URL::redirect('/admin');
		
		//Cria formulário
		$form = new \Form\Form('form_login');
		
		//Cria os campos do formulário
		$form->add_field(new \Form\TextInput('login', 'Login / E-mail'));
		$form->add_field(new \Form\Password('password', $sys_language->get('admin_login', 'password'), '', array(), false));
		$form->add_field(new \Form\Checkbox('remember_data', $sys_language->get('admin_login', 'remember_data'), 1));
		
		$form->add_html('<div class="button-container">');
		$form->add_field(new \Form\Button('login_button', $sys_language->get('admin_login', 'login_button')));
		$form->add_html('<a href="'.BASE.'" class="go-to-site">&laquo; '.$sys_language->get('admin_header', 'go_to').' <strong>'.TITLE.'</strong></a></div>');
		
		if($form->is_success()){
			$next_url = \HTTP\Request::get('next') ? \HTTP\Request::get('next') : '/admin';
			$remember_data = $form->get('remember_data') ? true : false;
			
			$sys_user->login(array('login' => array('field' => array('login', 'email'), 'value' => $form->get('login')), 'password' => array('field' => 'password', 'value' => $form->get('password')), 'extra' => array('active' => 1)), $next_url, true, $remember_data);
		}
		
		//Exibe o formulário
		$form->display(true, false);
	?>
</div>

<script>
	//Dá foco ao campo de login
	$('#login').focus();
</script>