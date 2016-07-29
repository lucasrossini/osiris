<?php
	//Instancia o administrador
	$admin_user = new \User\Admin();
	
	if($admin_user->is_logged()){
		//CSS
		echo '
			<link href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,400,700" rel="stylesheet" />
			<link href="admin/assets/css/bar.css" rel="stylesheet" />
		';
		
		//Módulos do sistema
		$modules = $admin_user->get_modules();
		$module_options = array();
		
		foreach($modules as $module_section => $packages_list)
			foreach($packages_list as $modules_list)
				foreach($modules_list as $module => $module_attr)
					$module_options[] = array('name' => $module_attr['name'], 'module' => $module, 'package' => $module_attr['package'], 'package_name' => \System\System::get_package_info($module_section, $module_attr['package'])->name, 'icon' => '/admin/modules/'.$module_section.'/'.$module_attr['package'].'/'.$module.'/icon-small.png');
		
		$modules_select = '
			<select onchange="location.href = this.value">
				<option value="">'.$sys_language->get('admin_bar', 'select_module').'</option>
		';
		
		$packages = array();
		
		foreach($module_options as $module_attr){
			if(!in_array($module_attr['package_name'], $packages)){
				if(sizeof($packages)){
					$modules_select .= '</optgroup>';
					$packages = Util\ArrayUtil::remove(end($packages), $packages);
				}
				
				$modules_select .= '<optgroup label="'.$module_attr['package_name'].'">';
				$packages[] = $module_attr['package_name'];
			}
			
			$modules_select .= '<option value="admin/'.$module_attr['package'].'/'.$module_attr['module'].'/list" style="background-image: url(\''.$module_attr['icon'].'\');">'.$module_attr['name'].'</option>';
		}
			
		$modules_select .= '
				</optgroup>
			</select>
		';
		
		//Verifica se o registro da página atual é editável
		if(($object_class = $sys_control->get_current_page_attr('class_name')) && $sys_control->get_current_page_attr('record_id')){
			$reflection_class = new ReflectionClass($object_class);
			
			if($reflection_class->hasConstant('ADMIN_PACKAGE') && $reflection_class->hasConstant('ADMIN_MODULE'))
				$edit_link = '<a href="admin/'.$reflection_class->getConstant('ADMIN_PACKAGE').'/'.$reflection_class->getConstant('ADMIN_MODULE').'?mode=edit&id='.$sys_control->get_current_page_attr('record_id').'">Editar registro</a> /';
		}
		
		//Exibe a barra
		echo '
			<div id="admin-bar" class="clearfix">
				<div class="left">'.$modules_select.'</div>

				<div class="right">
					<a href="admin">&laquo; '.$sys_language->get('admin_bar', 'go_to').' <strong>'.$sys_language->get('admin_footer', 'admin_system').'</strong></a>
					/ '.$edit_link.'
					<a href="admin/logout?next='.urlencode('/'.ltrim(str_replace(BASE, '', URL), '/')).'">'.$sys_language->get('admin_header', 'logout').'</a>
				</div>
			</div>
		';
	}
?>