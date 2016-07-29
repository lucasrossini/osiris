<link href="admin/modules/system/settings/modules/assets/styles.css" rel="stylesheet" />

<div id="modules-list">
	<?php
		//Carrega as informações do módulo na língua atual
		$module_language = \HTTP\Request::get('module_language');
		
		//Ativa/desativa um módulo
		if(\HTTP\Request::is_set('get', array('mod', 'action', 'section'))){
			$section = \HTTP\Request::get('section');
			$package = \HTTP\Request::get('pkg');
			$module = \HTTP\Request::get('mod');
			
			switch(\HTTP\Request::get('action')){
				case 1: //Ativar
					\System\System::activate_module($section, $package, $module);
					break;
				
				case 2: //Desativar
					\System\System::deactivate_module($section, $package, $module);
					break;
				
				case 3: //Remover
					\System\System::delete_module($section, $package, $module);
					break;
				
				default:
					\UI\Message::error($module_language->get('general', 'module_error'));
					break;
			}
		}
		
		//Carrega os módulos
		$modules_main_url = 'admin/settings/modules/main';
		$has_records = false;
		
		//Seções
		$html = '<div class="tabs-container">';
		$sections = array('system' => $module_language->get('general', 'system'), 'ecommerce' => 'E-Commerce', 'site' => 'Site');
		
		if(!ECOMMERCE)
			unset($sections['ecommerce']);
		
		foreach($sections as $module_section => $section_name)
			$html .= '<a href="#" class="tab" data-id="'.$module_section.'">'.$section_name.'</a>';
		
		$html .= '</div>';
		
		foreach($sections as $module_section => $section_name){
			$modules = \System\System::load_modules($module_section);
			
			if(sizeof($modules)){
				$has_records = true;
				$already_packages = array();
				
				$html .= '<div class="tab-content" data-id="'.$module_section.'">';
				
				foreach($modules as $module){
					//Carrega as informações do módulo
					$module_info = \System\System::load_module_info($module_section, $module['package'], $module['module']);
					
					//Carrega os dados do módulo
					$module_data = \System\System::load_module_data($module['package'], $module['module']);
					
					//Monta o link de ativação/desativação
					$activation_link = $module_data->active ? '<a href="'.$modules_main_url.'?section='.$module_section.'&mod='.$module['module'].'&pkg='.$module['package'].'&action=2#'.$module_section.'" class="icon disconnect">'.$module_language->get('general', 'deactivate').'</a>' : '<a href="'.$modules_main_url.'?section='.$module_section.'&mod='.$module['module'].'&pkg='.$module['package'].'&action=1#'.$module_section.'" class="icon connect">'.$module_language->get('general', 'activate').'</a>';
					$disabled_class = !$module_data->active ? 'disabled' : '';
					
					if(!in_array($module['package'], $already_packages) && !empty($module['package'])){
						$package_info = \System\System::get_package_info($module_section, $module['package']);
						$html .= '<h3 class="group">'.$package_info->name.'</h3>';
						
						$already_packages[] = $module['package'];
					}
					
					$html .= '
						<div class="label '.$disabled_class.'">
							<strong class="name" style="background-image:url(\'admin/modules/'.$module_section.'/'.$module['package'].'/'.$module['module'].'/icon-small.png\')">'.$module_info->name.'</strong>
							<p class="description">'.$module_info->description.'</p>
							
							<div class="bottom">
								<span class="path"><strong>'.$module_language->get('general', 'path').': </strong>/'.$module['package'].'/'.$module['module'].'</span>
								
								<div class="actions">
									'.$activation_link.'
									/
									<a href="'.$modules_main_url.'?section='.$module_section.'&mod='.$module['module'].'&pkg='.$module['package'].'&action=3#'.$module_section.'" class="icon no delete">'.$module_language->get('general', 'delete').'</a>
								</div>
								
								<div class="clear"></div>
							</div>
						</div>
					';
				}
				
				$html .= '</div>';
			}
		}
		
		if(!$has_records)
			$html .= '<p>'.$module_language->get('general', 'not_found').'</p>';
		
		echo $html;
	?>
</div>

<script>
	//Abas
	var hash = window.location.hash.replace('#', '');

	$('.tabs-container .tab').click(function(){
		$(this).siblings('.tab').removeClass('current');
		$('.tab-content').removeClass('current');
		$(this).addClass('current');
		$('.tab-content[data-id="' + $(this).data('id') + '"]').addClass('current');

		window.location.hash = $(this).data('id');
		return false;
	});

	if(hash)
		$('.tabs-container .tab[data-id="' + hash + '"]').click();
	else
		$('.tabs-container .tab:first').click();
</script>