<?php
	//Conjunto de caracteres do sistema
	header("Content-Type: text/html; charset=UTF-8", true);
	
	//Fuso horário
	ini_set('date.timezone', 'America/Sao_Paulo');
	
	//Diretórios
	define('DIR', '/');
	$_SESSION['DIR'] = DIR;
	
	define('ROOT', $_SERVER['DOCUMENT_ROOT'].rtrim(DIR, '/'));
	define('CORE_PATH', ROOT.'/app/core');
	define('LIB_PATH', ROOT.'/app/core/lib'.DIRECTORY_SEPARATOR);
	define('SITE_CLASS_PATH', ROOT.'/class'.DIRECTORY_SEPARATOR);
	
	//Nível de diretório a partir da URL base
	$dir_pieces = explode('/', DIR);
	$dir_level = 0;
	
	foreach($dir_pieces as $dir_piece){
		if(!empty($dir_piece))
			$dir_level++;
	}
	
	define('DIR_LEVEL', $dir_level);
	
	//IP
	define('LOCALHOST_IP', '127.0.0.1');
	define('LOCAL_SERVER_IP', '192.168.0.100');
	
	//SSL
	define('SSL', false);
	
	//E-Commerce
	define('ECOMMERCE', false);
	
	//Semente de números randômicos
	if(!isset($_SESSION['random_seed']))
		$_SESSION['random_seed'] = mt_rand(0, 100);
	
	define('RANDOM_SEED', $_SESSION['random_seed']);
?>