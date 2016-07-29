<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Campos
	$fields = array(
		'zip_code' => array(
			'save' => true,
			'validation' => array('is_empty')
		),
		'gift_price' => array(
			'save' => true,
			'type' => 'float',
			'validation' => array('is_decimal')
		)
	);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode('edit', false);
	$form->set_database_options(DAO\Ecommerce\Settings::TABLE_NAME, $fields, 1);
	
	//Cria os campos do formulário
	$form->add_html('<div class="inline-labels grid-4">');
	$form->add_field(new \Form\TextInput('zip_code', $module_language->get('form', 'zip_code'), '', array(), 'cep'));
	$form->add_field(new \Form\Money('gift_price', $module_language->get('form', 'gift_price')));
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