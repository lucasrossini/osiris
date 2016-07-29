<?php
	$action = (int)\HTTP\Request::get('a');
	
	if(HTTP\Request::get('save')){
		//Salva um novo item
		$item = HTTP\Request::post('item');
		$exists_sql = $insert_sql = $error = '';
		$exists = false;
		
		switch($action){
			case 1: //Tag de produto
				$exists_sql = 'SELECT COUNT(*) AS total FROM ecom_tag WHERE tag = "'.$item.'"';
				$insert_sql = 'INSERT INTO ecom_tag (tag, slug) VALUES ("'.$item.'", "'.\Formatter\String::slug($item).'")';
				
				break;
		}
		
		try{
			if($exists_sql){
				$db->query($exists_sql);
				$exists = $db->result(0)->total ? true : false;
			}
			
			if(!$exists){
				$item_id = $db->query($insert_sql);
			}
			else{
				$item_id = null;
				$error = $sys_language->get('ajax_autocomplete', 'already_exists');
			}
		}
		catch(Exception $e){
			$item_id = null;
		}
		
		$result = array('success' => $item_id ? true : false, 'id' => $item_id, 'error' => $error);
	}
	else{
		//Pesquisa
		$term = trim(HTTP\Request::get('term'));

		//Carrega os resultados
		if(!empty($term) || HTTP\Request::get('all')){
			switch($action){
				case 1: //Tags de produto
					$sql = 'SELECT id AS value, tag AS label FROM ecom_tag WHERE tag LIKE "%'.$term.'%" ORDER BY tag';
					break;
			}

			if($sql){
				$db->query($sql);
				$records = $db->result();
			}
		}

		//Monta os resultados
		$result = array();

		if(sizeof($records)){
			foreach($records as $record)
				$result[] = array('label' => $record->label, 'value' => $record->value);
		}
	}
?>