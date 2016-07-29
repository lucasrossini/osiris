<link href="admin/modules/system/admin/levels/assets/styles.css" rel="stylesheet" />

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
			'validation' => array(
				'is_empty',
				'already_exists' => array('table' => 'sys_admin_level', 'field' => 'name', 'ignore' => array(HTTP\Request::get('id')))
			)
		),
		'slug' => array(
			'save' => true,
			'type' => 'slug[name]'
		),
		'module_id' => array(
			'save' => true,
			'related' => true,
			'is_array' => true,
			'table' => 'sys_module_access_level'
		),
		'can_insert' => array(
			'save' => true,
			'related' => true,
			'is_array' => true,
			'table' => 'sys_module_access_level'
		),
		'can_edit' => array(
			'save' => true,
			'related' => true,
			'is_array' => true,
			'table' => 'sys_module_access_level'
		),
		'can_delete' => array(
			'save' => true,
			'related' => true,
			'is_array' => true,
			'table' => 'sys_module_access_level'
		),
		'can_view' => array(
			'save' => true,
			'related' => true,
			'is_array' => true,
			'table' => 'sys_module_access_level'
		)
	);
	
	//Relacionamentos
	$relationships = array(
		'sys_module_access_level' => array(
			'foreign_key' => 'level_id',
			'mode' => 'edit',
			'ignore' => array(\User\Admin::MASTER)
		)
	);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode($mode);
	$form->set_database_options('sys_admin_level', $fields, $id, $relationships);
	
	//Carrega os módulos do sistema
	$db->query('SELECT id, section, package, slug FROM sys_module WHERE active = 1 ORDER BY section DESC, package');
	$modules = $db->result();
	
	//Monta a tabela de permissões
	$table = '
		<table class="default-table permissions">
			<tr>
				<th>'.$module_language->get('form', 'module').'</th>
				<th colspan="4">'.$module_language->get('form', 'permissions').'</th>
			</tr>
	';
	
	$disabled = (($form->get_mode() == 'edit') && ((int)\HTTP\Request::get('id') === \User\Admin::MASTER)) ? 'disabled' : '';
	$sections = array('system' => $module_language->get('form', 'system'), 'site' => 'Site', 'ecommerce' => 'E-Commerce');
	
	if(!ECOMMERCE)
		unset($sections['ecommerce']);
	
	foreach($modules as $module){
		$module_info = \System\System::load_module_info($module->section, $module->package, $module->slug);
		$package_info = !empty($module->package) ? \System\System::get_package_info($module->section, $module->package) : '';
		$package_tag = !empty($package_info) ? '<span class="package-tag '.$module->section.'"><strong>'.$sections[$module->section].'</strong> / '.$package_info->name.'</span>' : '';
		
		$table .= '
			<tr>
				<td>
					'.$package_tag.'
					'.$form->add_field(new \Form\Hidden('module_id[]', $module_language->get('form', 'module'), $module->id), '', '', true).'
					<span class="module-name">'.$module_info->name.'</span>
				</td>
				
				<td class="check-cell">'.$form->add_field(new \Form\Checkbox('can_insert[]', $module_language->get('form', 'insert'), 1, array($disabled => $disabled)), '', '', true).'</td>
				<td class="check-cell">'.$form->add_field(new \Form\Checkbox('can_edit[]', $module_language->get('form', 'edit'), 1, array($disabled => $disabled)), '', '', true).'</td>
				<td class="check-cell">'.$form->add_field(new \Form\Checkbox('can_delete[]', $module_language->get('form', 'delete'), 1, array($disabled => $disabled)), '', '', true).'</td>
				<td class="check-cell">'.$form->add_field(new \Form\Checkbox('can_view[]', $module_language->get('form', 'view'), 1, array($disabled => $disabled)), '', '', true).'</td>
			</tr>
		';
	}
	
	$table .= '</table>';
	
	//Cria os campos do formulário
	$form->add_field(new \Form\TextInput('name', $module_language->get('form', 'name')));
	$form->add_label($module_language->get('form', 'modules'), '', $table);
	
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
	if($form->get_mode() == 'delete'){
		$db->query('SELECT id FROM sys_admin WHERE level_id = '.$form->get_record_id(), 'array', true);
		$admin_ids = \Util\ArrayUtil::count_items(array_keys($db->result()), 0, '', false);
		
		$before_delete_queries = array(
			'DELETE FROM sys_module_access_level WHERE level_id = '.$form->get_record_id(),
			!empty($admin_ids) ? 'DELETE FROM sys_admin_login_history WHERE admin_id IN ('.$admin_ids.')' : '',
			'DELETE FROM sys_admin WHERE level_id = '.$form->get_record_id()
		);
		
		$form->delete(true, array('id' => array(\User\Admin::MASTER), 'message' => $module_language->get('form', 'main_admin_message')), true, $before_delete_queries);
	}
	
	//Trata formulário após o envio
	$form->process();
?>