<?php
	//Carrega os resultados
	if(\HTTP\Request::is_set('get', array('id', 'a'))){
		$id = (int)\HTTP\Request::get('id');
		
		switch((int)\HTTP\Request::get('a')){
			case 1: //Cidades do estado
				$sql = 'SELECT id AS key_value, name AS text_value FROM sys_city WHERE state_id = '.$id.' ORDER BY name';
				break;
			
			case 2: //Subcategorias da categoria
				$sql = 'SELECT id AS key_value, name AS text_value FROM ecom_category WHERE parent_id = '.$id.' ORDER BY name';
				break;
		}
		
		$db->query($sql);
		$records = $db->result();
	}
	
	//Monta os resultados
	$records_array = array();
	
	if(sizeof($records)){
		$i = 0;
		
		if(!\HTTP\Request::get('default')){
			$i = 1;
			$records_array[0] = array('id' => '', 'value' => $sys_language->get('common', 'select'));
		}
		
		foreach($records as $record){
			$records_array[$i] = array('id' => $record->key_value, 'value' => $record->text_value);
			$i++;
		}
	}
	else{
		$records_array[0] = array('id' => '', 'value' => ((\HTTP\Request::get('id') !== '') && !\HTTP\Request::get('default')) ? $sys_language->get('ajax_load_options', 'no_records_found') : '');
	}
	
	$result = array('success' => true, 'records' => $records_array);
?>