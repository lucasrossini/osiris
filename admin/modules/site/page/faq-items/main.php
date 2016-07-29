<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Captura os parâmetros
	$id = (int)\HTTP\Request::get('id');
	$mode = strtolower(\HTTP\Request::get('mode'));
	
	//Campos
	$fields = array(
		'page_id' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_valid_option')
		),
		'question' => array(
			'save' => true,
			'validation' => array('is_empty')
		),
		'answer' => array(
			'save' => true,
			'validation' => array('is_empty')
		)
	);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode($mode);
	$form->set_database_options('sys_faq_item', $fields, $id);
	
	//Cria os campos do formulário
	$form->add_field(new \Form\Select('page_id', $module_language->get('form', 'faq_page'), '', array(), \Form\Select::load_options('sys_page', '[title]', 'is_faq = 1')));
	$form->add_field(new \Form\Textarea('question', $module_language->get('form', 'question'), '', array('class' => 'short')));
	$form->add_field(new \Form\Textarea('answer', $module_language->get('form', 'answer'), '', array('class' => 'tall')));
	
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
	
	//Apaga um registro
	$form->delete();
	
	//Trata formulário após o envio
	$form->process();
?>