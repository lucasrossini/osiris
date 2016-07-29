<link href="admin/modules/system/stats/stats/assets/styles.css" rel="stylesheet" />

<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Verifica a permissão de visualização de estatísticas
	$permission = $sys_user->get_permission('stats', 'stats', 'view');
	
	if($permission['granted']){
		//Carrega as configurações do Google Analytics
		$conf_ga = new \System\Config('ga');
		
		/*-- Visitas --*/
		
		try{
			$ga = new \Google\GoogleAnalytics($conf_ga->get('email'), \Security\Crypt::undo($conf_ga->get('password')));
			$ga->setProfile('ga:'.$conf_ga->get('profile_id'));
		
			//Total geral desde o lançamento do site
			$ga->setDateRange($sys_config->get('release_date'), date('Y-m-d'));
			$report = $ga->getReport(array('metrics' => urlencode('ga:visits')));
			$total_visits = $report['']['ga:visits'];

			//Total do mês atual
			$ga->setDateRange(date('Y-m-01'), date('Y-m-d'));
			$report = $ga->getReport(array('metrics' => urlencode('ga:visits')));
			$total_visits_month = $report['']['ga:visits'];

			//Total do dia atual
			$ga->setDateRange(date('Y-m-d'), date('Y-m-d'));
			$report = $ga->getReport(array('metrics' => urlencode('ga:visits')));
			$total_visits_day = $report['']['ga:visits'];

			echo '
				<div class="stats-line">
					<h3>
						<span class="title">'.$module_language->get('general', 'visits').'</span>
						<span class="count">'.sprintf($module_language->get('general', 'visits_count'), number_format($total_visits, 0, ',', '.'), \DateTime\Date::convert($sys_config->get('release_date'))).'</span>

						<div class="clear"></div>
					</h3>

					<div class="block">
						<h4>'.$module_language->get('general', 'month').' ('.\DateTime\Date::month_name(date('m')).')</h4>
						<span class="number">'.number_format($total_visits_month, 0, ',', '.').'</span>
					</div>

					<div class="block">
						<h4>'.$module_language->get('general', 'day').' ('.date('d').')</h4>
						<span class="number">'.number_format($total_visits_day, 0, ',', '.').'</span>
					</div>

					<div class="clear"></div>
				</div>
			';
		}
		catch(Exception $e){
			\UI\Message::permission_error($e->getMessage());
			\UI\Message::show_message('permission_error');
		}
	}
	else{
		echo \System\System::permission_error_message($permission['message']);
	}
?>