<?php
	//Inicia a sessão
	@session_start();
	
	//Configurações estáticas
	require_once 'settings/static.php';
	
	//Verifica a versão do servidor PHP
	$php_version = phpversion();
	
	if(version_compare($php_version, '5.3.0', '<'))
		exit('A versão do servidor PHP ('.$php_version.') é inválida! O sistema necessita da versão 5.3.0 ou mais recente.');
	
	//Autoload das classes
	if(!function_exists('__autoload')){
		function __autoload($class){
			if(class_exists($class))
				return;
			
			$class = ltrim(str_replace('\\', '/', $class), '/');
			$class_pieces = explode('/', $class);
			$class_pieces_size = sizeof($class_pieces);
			
			if($class_pieces_size > 1){
				$path = '';
				
				for($i = 0; $i < $class_pieces_size - 1; $i++)
					$path .= strtolower($class_pieces[$i]).'/';
				
				$class = $path.end($class_pieces);
			}
			
			if(is_file(LIB_PATH.$class.'.php'))
				require_once LIB_PATH.$class.'.php';

			if(is_file(SITE_CLASS_PATH.$class.'.php'))
				require_once SITE_CLASS_PATH.$class.'.php';
		}
	}
	
	//Conexão com o banco de dados
	require_once 'connection.php';
	
	//Configurações dinâmicas
	require_once 'settings/dynamic.php';
	
	//Controle de tempo de sessão
	require_once 'session.php';
	
	//Controle de segurança
	require_once 'security.php';
?>