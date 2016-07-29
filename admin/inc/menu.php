<?php
	echo '
		<nav id="menu-container">
			<div class="inner">
				<h2>
					<!-- <a href="#" title="'.$sys_language->get('admin_menu', 'hide_menu').'" class="tip menu-toggler"><img src="admin/images/icons/menu.png" alt="'.$sys_language->get('admin_menu', 'hide_menu').'" /></a> -->
					'.$sys_language->get('admin_menu', 'general').'
				</h2>
	';

	//Carrega todos os módulos
	$modules = $sys_user->get_modules();

	//Página inicial
	$current_class = $sys_control->is_home() ? 'current' : '';

	echo '
		<div class="menu">
			<a href="admin" class="home-menu '.$current_class.'" title="'.rtrim($sys_control->get_page_attr('/', 'subtitle'), '.').'"><span>'.$sys_control->get_page_attr('/', 'title').'</span></a>
			<a href="'.BASE.'" class="site-menu" title="'.$sys_language->get('admin_header', 'go_to').' '.TITLE.'"><span>'.\Formatter\String::truncate(TITLE, 20).'</span></a>
		</div>
	';

	//Exibe os módulos
	$sections = array('system' => $sys_language->get('admin_menu', 'system'), 'ecommerce' => 'E-Commerce', 'site' => 'Site');
	
	if(!ECOMMERCE)
		unset($sections['ecommerce']);
	
	foreach($sections as $section_slug => $section_name){
		if(sizeof($modules[$section_slug])){
			$already_packages = array();

			echo '
				<h2>'.$section_name.'</h2>
				<div class="menu">
			';

			foreach($modules[$section_slug] as $packages){
				foreach($packages as $module => $module_attr){
					if(!in_array($module_attr['package'], $already_packages) && !empty($module_attr['package'])){
						if(sizeof($already_packages)){
							echo '</div>';
							$last_group_closed = true;
						}

						$package_info = \System\System::get_package_info($section_slug, $module_attr['package']);

						echo '
							<h3 rel="menu_group_'.$section_slug.'_'.$module_attr['package'].'" class="closed">'.$package_info->name.'</h3>
							<div id="menu_group_'.$section_slug.'_'.$module_attr['package'].'" class="menu-group closed">
						';

						$last_group_closed = false;
						$already_packages[] = $module_attr['package'];
					}

					$current_class = (($module == $sys_control->get_page_attr($sys_control->get_url(), 'module')) && ($module_attr['package'] == $sys_control->get_page_attr($sys_control->get_url(), 'package'))) ? 'current' : '';
					echo '<a href="admin/'.$module_attr['package'].'/'.$module.'/list" class="'.$current_class.'" title="'.$module_attr['name'].'&#013;'.rtrim($module_attr['description'], '.').'"><span style="background-image:url(\'admin/modules/'.$section_slug.'/'.$module_attr['package'].'/'.$module.'/icon-small.png\')" class="truncate">'.$module_attr['name'].'</span></a>';
				}
			}

			if(!$last_group_closed)
				echo '</div>';

			echo '</div>';
		}
	}

	echo '
			</div>
		</nav>
	';
?>

<script>
	//Abre/fecha grupos de módulos do menu
	$('.menu h3').click(function(){
		$('#' + $(this).attr('rel')).slideToggle();
		$(this).toggleClass('closed');
	});
	
	//Deixa somente o menu que possui a página atual aberto
	$('#menu-container .menu .menu-group').each(function(){
		if($(this).find('.current').length){
			$(this).removeClass('closed').parent().find('h3[rel="' + $(this).attr('id') + '"]').removeClass('closed');
			return;
		}
	});
	
	//Exibe/esconde o menu
	$('#menu-container .menu-toggler').click(function(){
		$('#menu-container').toggleClass('hide');
		return false;
	});
</script>