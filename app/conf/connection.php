<?php	
	//Dados de conexão
	require 'settings/database.php';
	
	//Realiza a conexão com o banco de dados
	try{
		$db = new Database\Database($connection_data['host'], $connection_data['user'], $connection_data['pass'], $connection_data['base']);
	}
	catch(Exception $e){
		exit($e->getMessage());
	}
?>