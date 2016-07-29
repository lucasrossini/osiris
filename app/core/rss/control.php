<?php
	//Controle de RSS
	$url = $sys_control->get_url();
	
	//Exibe o RSS
	if($rss_data = $sys_control->check_rss($url)){
		$title = $rss_data['title'].' - '.TITLE;
		
		$rss = new \XML\RSS($title, $rss_data['url'], $rss_data['items'], $rss_data['description'], '/site/media/images/rss/logo.png');
		$rss->output();
	}
	
	//Mensagem de RSS inválido
	$xml = new \XML\XML(array('message' => $sys_language->get('rss', 'invalid_rss')), array('group' => 'error'));
	$xml->output();
?>