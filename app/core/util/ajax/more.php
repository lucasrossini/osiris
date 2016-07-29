<?php
	//Captura os parâmetros
	$action = (int)\HTTP\Request::get('a');
	$offset = (int)\HTTP\Request::get('offset');
	$count = (int)\HTTP\Request::get('count');
	$reverse = (int)\HTTP\Request::get('reverse');
	
	//Realiza a ação
	switch($action){
		default:
			$sql = '';
	}
	
	//Carrega os registros
	if(!empty($sql)){
		$db->query($sql.' LIMIT '.$offset.','.($count + 1));
		$records = $db->result();
	}
	elseif(isset($records)){
		$records = $records['results'];
	}
	
	$records_count = sizeof($records);
	
	if((int)$records_count === (int)($count + 1))
		$records = array_slice($records, 0, ($records_count - 1));
	
	//Inverte a ordem dos resultados
	if($reverse)
		$records = array_reverse($records);
	
	//Monta os resultados
	$result = array('has_more' => false, 'items' => array());
	
	if($records_count){
		$result['has_more'] = ($records_count >= ($count + 1));
		
		foreach($records as $record){
			switch($action){
				default:
					$content = '';
			}
			
			$result['items'][] = $content;
		}
	}
?>