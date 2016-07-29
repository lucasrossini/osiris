<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Campos
	$fields = array(
		'username' => array('save' => true),
		'oauth_access_token' => array('save' => true),
		'oauth_access_token_secret' => array('save' => true),
		'consumer_key' => array('save' => true),
		'consumer_secret' => array('save' => true)
	);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode('edit', false);
	$form->set_database_options('conf_twitter', $fields, 1);
	
	//Cria os campos do formulário
	$form->add_html('<div class="inline-labels grid-2">');
	$form->add_field(new \Form\TextInput('username', $module_language->get('form', 'username'), '', array('class' => 'small')), $sys_language->get('common', 'example').' http://twitter.com/<strong>username</strong>');
	$form->add_html('</div>');
	
	$form->add_html('<div class="inline-labels grid-2">');
	$form->add_field(new \Form\TextInput('oauth_access_token', $module_language->get('form', 'oauth_access_token')));
	$form->add_field(new \Form\TextInput('oauth_access_token_secret', $module_language->get('form', 'oauth_access_token_secret')));
	$form->add_html('</div>');
	
	$form->add_html('<div class="inline-labels grid-2">');
	$form->add_field(new \Form\TextInput('consumer_key', $module_language->get('form', 'consumer_key')));
	$form->add_field(new \Form\TextInput('consumer_secret', $module_language->get('form', 'consumer_secret')));
	$form->add_html('</div>');
	
	$form->add_html('<div class="button-container">');
	$form->add_field(new \Form\Button('submit_button'));
	$form->add_html('</div>');
	
	//Detecta alterações no formulário
	$form->detect_changes();
	
	//Exibe o formulário
	$form->display();
	
	//Trata formulário após o envio
	$form->process();
?>