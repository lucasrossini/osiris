<?php
	//Captura informações da URL
	$url = $sys_control->get_url();
	$url_info = \Storage\File::split_path($url);
	
	//Processa a chamada da API
	$this->api_call($url_info['path'].'/'.\Storage\File::name($url_info['file']), \Storage\File::extension($url_info['file']));
?>