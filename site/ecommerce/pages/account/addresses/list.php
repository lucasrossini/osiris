<link rel="stylesheet" href="/site/ecommerce/assets/css/account.css" />
<link rel="stylesheet" href="/site/ecommerce/assets/css/addresses.css" />

<?php
	//Cabeçalho da conta
	include 'ecommerce/inc/account-header.php';
	
	//Menu da conta
	include 'ecommerce/inc/account-menu.php';
?>

<section id="addresses">
	<h1>Meus endereços</h1>
	
	<div class="address-list">
		<?php
			$client_id = $sys_user->get('id');
			
			//Define um endereço como principal
			if(\HTTP\Request::is_set('get', 'action')){
				$action = \HTTP\Request::get('action');
				
				switch($action){
					case 'set':
						$id = (int)\HTTP\Request::get('id');
						
						$db->init_transaction();
						$db->query('UPDATE '.\DAO\Ecommerce\Address::TABLE_NAME.' SET `default` = 0 WHERE client_id = '.$client_id);
						$db->query('UPDATE '.\DAO\Ecommerce\Address::TABLE_NAME.' SET `default` = 1 WHERE client_id = '.$client_id.' AND id = '.$id);
						$transaction_result = $db->end_transaction();
						
						if($transaction_result['success'])
							\UI\Message::success('Endereço definido como principal com sucesso!');
						else
							\UI\Message::error('Falha ao definir endereço como principal! Tente novamente.');
						
						break;
				}
				
				\URL\URL::redirect('/minha-conta/enderecos');
			}
			
			//Carrega os endereços do cliente
			$addresses = \DAO\Ecommerce\Address::load_all('SELECT id FROM '.DAO\Ecommerce\Address::TABLE_NAME.' WHERE client_id = '.$client_id.' ORDER BY `default` DESC, title');

			//Exibe os endereços
			$html = '';

			foreach($addresses['results'] as $address){
				if($address->get('default')){
					$default_class = 'default';
					$set_default_html = '';
				}
				else{
					$default_class = '';
					$set_default_html = '<a href="/minha-conta/enderecos?action=set&id='.$address->get('id').'" class="set-default">Tornar principal</a>';
				}

				$complement = $address->get('complement') ? ' / '.$address->get('complement') : '';

				$html .= '
					<address class="'.$default_class.'">
						<h3>'.$address->get('title').'</h3>

						<p>'.$address->get('addressee').'</p>
						<p>'.$address->get('street').', '.$address->get('number').$complement.'</p>
						<p>'.$address->get('neighborhood').' - '.$address->get('zip_code').'</p>
						<p>'.$address->get('city')->get('name').', '.$address->get('state')->get('acronym').'</p>

						<div class="buttons">
							'.$set_default_html.'
							<a href="/minha-conta/enderecos/formulario?mode=edit&id='.$address->get('id').'" class="edit">Editar</a>
							<a href="/minha-conta/enderecos/formulario?mode=delete&id='.$address->get('id').'" class="delete">Excluir</a>
						</div>
					</address>
				';
			}

			$html .= '
				<address class="new">
					<a href="/minha-conta/enderecos/formulario">
						<span class="plus">+</span>
						Cadastrar novo endereço
					</a>
				</address>
			';

			echo $html;
		?>
	</div>
</section>