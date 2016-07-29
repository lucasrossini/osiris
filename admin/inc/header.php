<?php
	//Carrega título, subtítulo e ícone do módulo atual
	if($sys_control->not_found()){
		$icon = $sys_control->get_page_attr(\System\Control::URL_404, 'icon');
		$title = $sys_control->get_page_attr(\System\Control::URL_404, 'title');
		$subtitle = $sys_control->get_page_attr(\System\Control::URL_404, 'subtitle');
		
		$package = '';
	}
	else{
		$current_page = $sys_control->get_url();
		
		$icon = $sys_control->get_page_attr($current_page, 'icon');
		$title = !$sys_control->get_page_attr($current_page, 'module_name') ? $sys_control->get_page_attr($current_page, 'title') : $sys_control->get_page_attr($current_page, 'module_name');
		$subtitle = $sys_control->get_page_attr($current_page, 'subtitle');
		
		$package = $sys_control->get_page_attr($current_page, 'package');
		$section = $sys_control->get_page_attr($current_page, 'section');
	}
	
	//Concatena o nome do pacote caso o módulo faça parte de um
	if(!empty($package) && ($package != 'default'))
		$title = \System\System::get_package_info($section, $package)->name.' &rsaquo; '.$title;
	
	//Exibe o título
	echo '
		<header id="header">
			<hgroup>
				<h1>
					<span class="icon" style="background-image: url(\''.$icon.'\')">'.$title.'</span>
				</h1>
				
				<h2>
					'.$subtitle.'
					<span class="date">'.\DateTime\Date::get_current_long_date().'</span>
				</h2>
			</hgroup>
		</header>
	';
?>