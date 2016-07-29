<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Campos
	$fields = array(
		'contact_email' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_email')
		),
		'host' => array(
			'save' => true,
			'validation' => array('is_empty')
		),
		'user' => array(
			'save' => true,
			'validation' => array('is_empty')
		),
		'password' => array(
			'save' => true,
			'type' => 'password',
			'validation' => array('is_empty')
		)
	);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode('edit', false);
	$form->set_database_options('conf_email', $fields, 1);
	
	//Cria os campos do formulário
	$form->add_html('<div class="inline-labels grid-3">');
	$form->add_field(new \Form\TextInput('contact_email', $module_language->get('form', 'contact_email')));
	$form->add_html('</div>');
	
	$form->add_html('<div class="inline-labels grid-3">');
	$form->add_field(new \Form\TextInput('host', $module_language->get('form', 'email_host')));
	$form->add_field(new \Form\TextInput('user', $module_language->get('form', 'email_user')));
	$form->add_field(new \Form\Password('password', $module_language->get('form', 'email_password'), array(), true, false));
	$form->add_html('</div>');
	
	$form->add_html('<div class="button-container">');
	$form->add_field(new \Form\Button('submit_button'));
	$form->add_html('</div>');
	
	//Valida o formulário
	$form->validate();
	
	//Detecta alterações no formulário
	$form->detect_changes();
	
	//Exibe o formulário
	$form->display();
	
	//Trata formulário após o envio
	$form->process();
?>