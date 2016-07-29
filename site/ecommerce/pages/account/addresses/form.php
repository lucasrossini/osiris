<link rel="stylesheet" href="/site/ecommerce/assets/css/account.css" />

<?php
	//Cabeçalho da conta
	include 'ecommerce/inc/account-header.php';
	
	//Menu da conta
	include 'ecommerce/inc/account-menu.php';
?>

<section id="address-form">
	<h1>Cadastrar endereço</h1>
	
	<?php
		//Captura os parâmetros
		$client_id = $sys_user->get('id');
		$mode = 'insert';
		$id = null;
		
		if(\HTTP\Request::is_set('get', array('mode', 'id'))){
			$id = (int)\HTTP\Request::get('id');
			$mode = \HTTP\Request::get('mode');
			
			if(in_array($mode, array('edit', 'delete'))){
				//Verifica se é um endereço válido do usuário
				$db->query('SELECT COUNT(*) AS total FROM '.DAO\Ecommerce\Address::TABLE_NAME.' WHERE id = '.$id.' AND client_id = '.$client_id);

				if(!$db->result(0)->total){
					\UI\Message::error('Endereço inválido!');
					\URL\URL::redirect('/minha-conta/enderecos');
				}
			}
		}
		
		//Campos
		$fields = array(
			'title' => array(
				'save' => true,
				'validation' => array('is_empty')
			),
			'addressee' => array(
				'save' => true,
				'validation' => array('is_empty')
			),
			'zip_code' => array(
				'save' => true,
				'validation' => array('is_empty')
			),
			'street' => array(
				'save' => true,
				'validation' => array('is_empty')
			),
			'number' => array(
				'save' => true,
				'validation' => array('is_empty', 'is_number')
			),
			'complement' => array(
				'save' => true
			),
			'neighborhood' => array(
				'save' => true,
				'validation' => array('is_empty')
			),
			'state_id' => array(
				'save' => true,
				'validation' => array('is_empty', 'is_valid_option')
			),
			'city_id' => array(
				'save' => true,
				'validation' => array('is_empty')
			),
			'default' => array(
				'save' => true,
				'type' => 'boolean'
			),
			'client_id' => array(
				'save' => true,
				'value' => $client_id
			)
		);
		
		//Cria formulário
		$form = new \Form\Form('form_address');
		$form->set_mode($mode, false);
		$form->set_database_options(DAO\Ecommerce\Address::TABLE_NAME, $fields, $id);
		
		$form->add_html('<div class="inline-labels grid-3">');
		$form->add_field(new \Form\TextInput('title', 'Título'));
		$form->add_field(new \Form\TextInput('addressee', 'Destinatário'));
		$form->add_field(new \Form\TextInput('zip_code', 'CEP', '', array(), 'cep'));
		$form->add_html('</div>');
		
		$form->add_html('<div class="inline-labels grid-3">');
		$form->add_field(new \Form\TextInput('street', 'Rua'));
		$form->add_field(new \Form\Number('number', 'Número'));
		$form->add_field(new \Form\TextInput('complement', 'Complemento'));
		$form->add_html('</div>');
		
		$form->add_html('<div class="inline-labels grid-3">');
		$form->add_field(new \Form\TextInput('neighborhood', 'Bairro'));
		$form->add_field(new \Form\Select('state_id', 'Estado', '', array(), Form\Select::load_options('sys_state', '[name]', 'TRUE', 'name')));
		$form->add_field(new \Form\Select('city_id', 'Cidade', '', array(), array('' => 'Selecione um estado')));
		$form->add_html('</div>');
		
		$form->add_field(new \Form\Checkbox('default', 'Definir como meu endereço principal', 1));

		$form->add_html('<div class="button-container">');
		$form->add_field(new \Form\Button('submit_button'));
		$form->add_field(new \Form\Button('cancel_button', $sys_language->get('common', 'cancel'), '', array(), 'button'));
		$form->add_html('</div>');

		//Valida o formulário
		$form->validate();

		//Exibe o formulário
		$form->display();

		//Trata formulário após o envio
		$messages = array(
			'insert' => array(
				'success' => 'Endereço cadastrado com sucesso!',
				'error' => 'Falha ao cadastrar endereço! Tente novamente.',
			),
			'edit' => array(
				'success' => 'Endereço atualizado com sucesso!',
				'error' => 'Falha ao atualizar endereço! Tente novamente.',
			)
		);
		
		if($form->process(false, array(), true, $messages)){
			$process_id = $form->get_process_id();
			
			if($form->get('default')){
				//Desmarca outros endereços como padrão
				$db->query('UPDATE '.DAO\Ecommerce\Address::TABLE_NAME.' SET `default` = 0 WHERE client_id = '.$client_id.' AND id != '.$process_id);
			}
			else{
				//Define o primeiro endereço como padrão caso nenhum seja mais padrão
				$db->query('SELECT COUNT(*) AS total FROM '.DAO\Ecommerce\Address::TABLE_NAME.' WHERE `default` = 1 AND client_id = '.$client_id);
				
				if(!$db->result(0)->count){
					$db->query('SELECT id FROM '.DAO\Ecommerce\Address::TABLE_NAME.' WHERE client_id = '.$client_id.' ORDER BY id LIMIT 0,1');
					$address_id = $db->result(0)->id;
					
					$db->query('UPDATE '.DAO\Ecommerce\Address::TABLE_NAME.' SET `default` = 1 WHERE id = '.$address_id);
				}
			}
			
			$url = HTTP\Request::is_set('get', 'checkout') ? '/checkout?address='.$process_id : '/minha-conta/enderecos';
			\URL\URL::redirect($url);
		}
	?>
</section>

<script>
	$(document).ready(function(){
		//Carrega as cidades do estado
		$('#state_id').change(function(){
			Ajax.load_select_options({a: 1, id: $(this).val()}, $('#city_id'));
		});
		
		//Carrega o endereço do CEP
		var last_zip_code = '<?php echo $form->get('zip_code') ?>';
		
		$('#zip_code').blur(function(){
			if(($(this).val() != last_zip_code) && ($(this).val().length == 9)){
				Ajax.load_zip_address($(this).val(), $('#street'), $('#neighborhood'), $('#state_id'), $('#city_id'));
				$('#number').focus();
			}
			
			last_zip_code = $(this).val();
		});
	});
	
	//Cancelar
	$('#cancel_button').click(function(){
		window.location.href = '/minha-conta/enderecos';
	});
</script>