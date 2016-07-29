<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Campos
	$fields = array(
		'user' => array(
			'save' => true,
			'validation' => array('is_email')
		),
		'token' => array('save' => true)
	);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode('edit', false);
	$form->set_database_options('conf_pagseguro', $fields, 1);
	
	//Cria os campos do formulário
	$form->add_html('<div class="inline-labels">');
	$form->add_field(new \Form\TextInput('user', $module_language->get('form', 'email'), '', array(), 'email'));
	$form->add_field(new \Form\TextInput('token', $module_language->get('form', 'token')));
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