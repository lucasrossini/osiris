<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Captura os parâmetros
	$id = (int)\HTTP\Request::get('id');
	$mode = strtolower(\HTTP\Request::get('mode'));
	
	//Campos
	$init_date = new \Form\Date('init_date', $module_language->get('form', 'init_date'));
	
	$fields = array(
		'type_id' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_valid_option')
		),
		'name' => array(
			'save' => true,
			'validation' => array('is_empty')
		),
		'url' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_url')
		),
		'new_window' => array(
			'save' => true,
			'type' => 'boolean'
		),
		'file_type' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_valid_option')
		),
		'file' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_file')
		),
		'init_date' => array(
			'save' => true,
			'type' => 'date',
			'validation' => array('is_empty', 'is_date')
		),
		'end_date' => array(
			'save' => true,
			'type' => 'date',
			'validation' => array(
				'is_empty',
				'is_date',
				'compare' => array('type' => 'greater', 'with' => $init_date)
			)
		),
		'forced_width' => array(
			'save' => true,
			'type' => 'int',
			'validation' => array('is_number')
		),
		'forced_height' => array(
			'save' => true,
			'type' => 'int',
			'validation' => array('is_number')
		)
	);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode($mode);
	$form->set_database_options(\DAO\Banner::TABLE_NAME, $fields, $id, array(), '\DAO\Banner');
	
	//Cria os campos do formulário
	$form->add_html('<div class="inline-labels">');
	$form->add_field(new \Form\Select('type_id', $module_language->get('form', 'banner_type'), '', array(), \Form\Select::load_options(\DAO\BannerType::TABLE_NAME, '[name] ([width]px x [height]px)')));
	$form->add_field(new \Form\TextInput('name', $module_language->get('form', 'name')));
	$form->add_html('</div>');
	
	$form->add_html('<div class="inline-labels">');
	$form->add_field(new \Form\TextInput('url', 'URL', '', array(), 'url'));
	$form->add_field(new \Form\Checkbox('new_window', $module_language->get('form', 'new_window'), 1));
	$form->add_html('</div>');
	
	$form->add_html('<div class="inline-labels">');
	$form->add_field(new \Form\RadioGroup('file_type', $module_language->get('form', 'file_type'), '', array(), array(\DAO\Banner::IMAGE => 'Imagem', \DAO\Banner::FLASH => 'Flash')));
	$form->add_field(new \Form\Upload('file', $module_language->get('form', 'file'), array(), array(), '/uploads/banners/', true, 1, array('jpg', 'jpeg', 'gif', 'png', 'swf')));
	$form->add_html('</div>');
	
	$form->add_html('<div class="inline-labels">');
	$form->add_field($init_date);
	$form->add_field(new \Form\Date('end_date', $module_language->get('form', 'end_date')));
	$form->add_html('</div>');
	
	$form->add_html('<div class="inline-labels">');
	$form->add_field(new \Form\Number('forced_width', $module_language->get('form', 'width'), '', array(), 'px'));
	$form->add_field(new \Form\Number('forced_height', $module_language->get('form', 'height'), '', array(), 'px'));
	$form->add_html('<div class="clear"></div><p class="field-tip">'.$module_language->get('form', 'forced_dimensions_tip').'</p></div>');
	
	if($form->get_mode() == 'view'){
		$form->add_html('<div class="inline-labels">');
		$form->add_field(new \Form\Number('views', $module_language->get('form', 'views')));
		$form->add_field(new \Form\Number('clicks', $module_language->get('form', 'clicks')));
		$form->add_html('</div>');
	}
	
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