<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Captura os parâmetros
	$id = (int)\HTTP\Request::get('id');
	$mode = strtolower(\HTTP\Request::get('mode'));
	
	//Campos
	$is_rotative = new \Form\Checkbox('is_rotative', $module_language->get('form', 'rotative'), 1);
	
	$fields = array(
		'name' => array(
			'save' => true,
			'validation' => array('is_empty')
		),
		'width' => array(
			'save' => true,
			'type' => 'int',
			'validation' => array('is_empty', 'is_number')
		),
		'height' => array(
			'save' => true,
			'type' => 'int',
			'validation' => array('is_empty', 'is_number')
		),
		'is_popup' => array(
			'save' => true,
			'type' => 'boolean'
		),
		'is_rotative' => array(
			'save' => true,
			'type' => 'boolean'
		),
		'delay' => array(
			'save' => true,
			'type' => 'int',
			'validation' => array(
				'requires' => array('object' => $is_rotative, 'validation' => array('is_empty')),
				'is_number'
			)
		)
	);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode($mode);
	$form->set_database_options(\DAO\BannerType::TABLE_NAME, $fields, $id, array(), '\DAO\BannerType');
	
	//Cria os campos do formulário
	$form->add_html('<div class="inline-labels grid-3">');
	$form->add_field(new \Form\TextInput('name', $module_language->get('form', 'name')));
	$form->add_field(new \Form\Number('width', $module_language->get('form', 'width'), '', array(), 'px'));
	$form->add_field(new \Form\Number('height', $module_language->get('form', 'height'), '', array(), 'px'));
	$form->add_html('</div>');
	
	$form->add_html('<div class="inline-labels grid-3">');
	$form->add_field(new \Form\Checkbox('is_popup', 'Pop-up', 1), $module_language->get('form', 'popup_tip'));
	$form->add_field($is_rotative, $module_language->get('form', 'rotative_tip'));
	$form->add_field(new \Form\Number('delay', $module_language->get('form', 'delay'), '', array(), $module_language->get('form', 'seconds')));
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
	
	//Apaga um registro
	$form->delete();
	
	//Trata formulário após o envio
	$form->process();
?>