<?php
	//Realiza as operações
	$action = (int)\HTTP\Request::get('a');
	$data = $error_message = '';
	$success = false;
	
	switch($action){
		case 1: //Registra opinião sobre um item de FAQ
			$id = (int)\HTTP\Request::get('id');
			$vote = (int)\HTTP\Request::get('vote');

			switch($vote){
				case \DAO\Page::VOTE_YES:
					$field = 'useful_votes';
					break;

				case \DAO\Page::VOTE_NO:
					$field = 'useless_votes';
					break;
			}

			if($db->query('UPDATE sys_faq_item SET '.$field.' = '.$field.' + 1 WHERE id = '.$id)){
				$success = true;
				$data = '<span class="thank-you">'.$sys_language->get('ajax_post_data', 'vote_thanks').'</span>';
				\HTTP\Session::create('voted_faq_item_'.$id, true);
			}

			break;

		case 2: //Carrega o endereço de um CEP
			$response = \Correios\ZipCode::get_address(\HTTP\Request::get('zip_code'), true);

			if($response !== false){
				$success = true;
				$data = $response;
			}
			else{
				$error_message = $sys_language->get('ajax_post_data', 'invalid_zip');
			}

			break;

		case 3: //Calcula o frete
			$response = \Correios\Shipping::calculate(\HTTP\Request::get('service'), \HTTP\Request::get('origin'), \HTTP\Request::get('destiny'), \HTTP\Request::get('dimensions'), \HTTP\Request::get('value'));

			if($response['success']){
				$success = true;
				$data = $response;
			}
			else{
				$error_message = $response['error'];
			}

			break;
	}
	
	//Retorna o conteúdo
	$result = $success ? array('success' => true, 'data' => $data) : array('success' => false, 'error' => $error_message);
?>