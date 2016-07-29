<?php
	//Dados de conexão
	switch($_SERVER['SERVER_ADDR']){
		case LOCALHOST_IP: //Máquina local
			$connection_data = array(
				'host' => 'localhost',
				'user' => 'root',
				'pass' => '',
				'base' => 'osiris'
			);
			
			break;
		
		case LOCAL_SERVER_IP: //Servidor local
			$connection_data = array(
				'host' => 'localhost',
				'user' => 'root',
				'pass' => 'rainha10',
				'base' => 'osiris'
			);
			
			break;
		
		default: //Internet
			$connection_data = array(
				'host' => '',
				'user' => '',
				'pass' => '',
				'base' => ''
			);
			
			break;
	}
?>