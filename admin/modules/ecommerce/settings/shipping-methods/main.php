<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Captura os parâmetros
	$id = (int)\HTTP\Request::get('id');
	$mode = strtolower(\HTTP\Request::get('mode'));
	
	//Campos
	$fields = array(
		'name' => array(
			'save' => true,
			'validation' => array('is_empty')
		),
		'price' => array(
			'save' => true,
			'type' => 'float',
			'validation' => array('is_decimal')
		),
		'unit' => array(
			'save' => true,
			'validation' => array('is_valid_option')
		),
		'delivery_days' => array(
			'save' => true,
			'type' => 'int',
			'validation' => array('is_number')
		),
		'active' => array(
			'save' => true,
			'type' => 'boolean'
		),
	);
	
	//Valor cobrado por
	$unit_options = array(\DAO\Ecommerce\ShippingMethod::PER_PRODUCT => $module_language->get('form', 'product'), \DAO\Ecommerce\ShippingMethod::PER_ORDER => $module_language->get('form', 'order'));
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode($mode);
	$form->set_database_options(DAO\Ecommerce\ShippingMethod::TABLE_NAME, $fields, $id);
	
	//Cria os campos do formulário
	$form->add_html('<div class="inline-labels grid-3">');
	$form->add_field(new \Form\TextInput('name', $module_language->get('form', 'name')));
	$form->add_field(new \Form\Number('delivery_days', $module_language->get('form', 'delivery_days'), '', array(), $module_language->get('form', 'days')));
	$form->add_field(new \Form\Money('price', $module_language->get('form', 'price')));
	$form->add_html('</div>');
	
	$form->add_field(new \Form\RadioGroup('unit', $module_language->get('form', 'unit'), '', array(), $unit_options));
	$form->add_field(new \Form\Checkbox('active', $module_language->get('form', 'active'), 1, array(), true));
	
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
	$form->delete(true, array('id' => array(1, 2, 3, 4)));
	
	//Trata formulário após o envio
	$form->process();
?>