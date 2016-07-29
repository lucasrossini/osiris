<style>
	.address > h3 > .remove{
		float: right;
		font-size: 14px;
		font-weight: normal;
		margin-top: 2px;
	}
	
	#label-default > .checkbox{
		display: block;
		margin-top: 36px;
	}
</style>

<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Captura os parâmetros
	$id = (int)\HTTP\Request::get('id');
	$mode = strtolower(\HTTP\Request::get('mode'));
	
	//Campos
	$user_address_table = DAO\Ecommerce\Address::TABLE_NAME;
	
	$fields = array(
		'name' => array(
			'save' => true,
			'validation' => array('is_empty')
		),
		'cpf' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_cpf', 'already_exists')
		),
		'phone' => array(
			'save' => true,
			'validation' => array('is_empty')
		),
		'email' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_email', 'already_exists')
		),
		'password' => array(
			'save' => true,
			'type' => 'password',
			'validation' => array('is_empty')
		),
		'signup_date' => array(
			'save' => true,
			'type' => 'curdate'
		),
		'signup_time' => array(
			'save' => true,
			'type' => 'curtime'
		),
		'title' => array(
			'save' => true,
			'related' => true,
			'table' => 'ecom_address',
			'is_array' => true,
			'validation' => array('is_empty')
		),
		'addressee' => array(
			'save' => true,
			'related' => true,
			'table' => 'ecom_address',
			'is_array' => true,
			'validation' => array('is_empty')
		),
		'zip_code' => array(
			'save' => true,
			'related' => true,
			'table' => $user_address_table,
			'is_array' => true,
			'validation' => array('is_empty')
		),
		'street' => array(
			'save' => true,
			'related' => true,
			'table' => $user_address_table,
			'is_array' => true,
			'validation' => array('is_empty')
		),
		'number' => array(
			'save' => true,
			'related' => true,
			'table' => $user_address_table,
			'is_array' => true,
			'validation' => array('is_empty', 'is_number')
		),
		'complement' => array(
			'save' => true,
			'related' => true,
			'table' => $user_address_table,
			'is_array' => true
		),
		'neighborhood' => array(
			'save' => true,
			'related' => true,
			'table' => $user_address_table,
			'is_array' => true,
			'validation' => array('is_empty')
		),
		'state_id' => array(
			'save' => true,
			'related' => true,
			'table' => $user_address_table,
			'is_array' => true,
			'validation' => array('is_empty', 'is_valid_option')
		),
		'city_id' => array(
			'save' => true,
			'related' => true,
			'table' => $user_address_table,
			'is_array' => true,
			'validation' => array('is_empty')
		),
		'default' => array(
			'save' => true,
			'related' => true,
			'table' => $user_address_table,
			'is_array' => true
		)
	);
	
	//Relacionamentos
	$relationships = array(
		$user_address_table => array(
			'foreign_key' => 'client_id',
			'mode' => 'edit'
		)
	);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode($mode);
	$form->set_database_options(DAO\Ecommerce\Client::TABLE_NAME, $fields, $id, $relationships);
	
	//Total de endereços
	if($form->is_submitted()){
		$total_addresses = sizeof($form->get('title'));
	}
	else{
		$db->query('SELECT COUNT(*) AS total FROM '.DAO\Ecommerce\Address::TABLE_NAME.' WHERE client_id = '.$id);
		$total_addresses = $db->result(0)->total;
	}
	
	if(!$total_addresses)
		$total_addresses = 1;
	
	//Cria os campos do formulário
	$form->init_tab('personal_data', $module_language->get('tabs', 'personal_data')); //Dados pessoais
	$form->add_html('<div class="inline-labels grid-3">');
	$form->add_field(new \Form\TextInput('name', $module_language->get('form', 'name')));
	$form->add_field(new \Form\TextInput('cpf', 'CPF', '', array(), 'cpf'));
	$form->add_field(new \Form\TextInput('phone', $module_language->get('form', 'phone'), '', array(), 'phone'));
	$form->add_html('</div>');

	$form->add_html('<div class="inline-labels grid-3">');
	$form->add_field(new \Form\TextInput('email', 'E-mail', '', array(), 'email'));
	$form->add_field(new \Form\Password('password', $module_language->get('form', 'password')));
	$form->add_html('</div>');
	$form->end_tab();
	
	$form->init_tab('addresses', $module_language->get('tabs', 'addresses')); //Endereços
	
	for($i = 1; $i <= $total_addresses; $i++){
		$form->add_html('<div class="address"><h3 class="group">'.$module_language->get('form', 'address').' <a href="#" class="remove">'.$module_language->get('form', 'remove').'</a></h3><div class="inline-labels">');
		$form->add_field(new \Form\TextInput('title[]', $module_language->get('form', 'title')));
		$form->add_field(new \Form\TextInput('addressee[]', $module_language->get('form', 'addressee')));
		$form->add_html('</div>');

		$form->add_html('<div class="inline-labels grid-4">');
		$form->add_field(new \Form\TextInput('zip_code[]', $module_language->get('form', 'zip_code'), '', array(), 'cep'));
		$form->add_field(new \Form\TextInput('street[]', $module_language->get('form', 'street')));
		$form->add_field(new \Form\Number('number[]', $module_language->get('form', 'number')));
		$form->add_field(new \Form\TextInput('complement[]', $module_language->get('form', 'complement')));
		$form->add_html('</div>');

		$form->add_html('<div class="inline-labels grid-4">');
		$form->add_field(new \Form\TextInput('neighborhood[]', $module_language->get('form', 'neighborhood')));
		$form->add_field(new \Form\Select('state_id[]', $module_language->get('form', 'state'), '', array(), Form\Select::load_options('sys_state', '[name]', 'TRUE', 'name')));
		$form->add_field(new \Form\Select('city_id[]', $module_language->get('form', 'city'), '', array(), array('' => 'Selecione um estado')));
		$form->add_field(new \Form\Radio('default[]', $module_language->get('form', 'default'), 1));
		$form->add_html('</div></div>');
	}
	
	$form->add_html('<div class="label"><a href="#" id="add-address" class="icon add">'.$module_language->get('form', 'add_address').'</a></div>');
	$form->end_tab();
	
	$form->add_html('<div class="button-container">');
	$form->add_field(new \Form\Button('submit_button'));
	$form->add_field(new \Form\Button('cancel_button', $sys_language->get('common', 'cancel'), '', array(), 'button'));
	$form->add_html('</div>');
	
	//Valida o formulário
	$form->validate();
	
	//Exibe o formulário
	$form->display();
	
	//Apaga um registro
	$form->delete();
	
	//Trata formulário após o envio
	$form->process();
?>

<script>
	$(document).ready(function(){
		//Carrega as cidades do estado
		$('select[name="state_id[]"]').live('change', function(){
			Ajax.load_select_options({a: 1, id: $(this).val()}, $(this).parents('.inline-labels:first').find('select[name="city_id[]"]'), false, true);
		});
		
		//Carrega o endereço do CEP
		var last_zip_code = '';
		
		$('input[name="zip_code[]"]').live('focus', function(){
			console.log('focus = ' + $(this).val());
			last_zip_code = $(this).val();
		});
		
		$('input[name="zip_code[]"]').live('blur', function(){
			if(($(this).val() != last_zip_code) && ($(this).val().length == 9)){
				var address_container = $(this).parents('.address:first');
				
				Ajax.load_zip_address($(this).val(), address_container.find('input[name="street[]"]'), address_container.find('input[name="neighborhood[]"]'), address_container.find('select[name="state_id[]"]'), address_container.find('select[name="city_id[]"]'));
				address_container.find('input[name="number[]"]').focus();
			}
			
			last_zip_code = $(this).val();
		});
		
		//Adiciona um endereço
		$('#add-address').click(function(){
			var address_container = $('.address:first').clone();
			
			address_container.find('input, select').val('').removeAttr('checked').end().find('input[name="zip_code[]"]').mask('99999-999').end().find('select[name="city_id[]"]').data('default_text', $('.address:first select[name="city_id[]"]').data('default_text')).removeAttr('data-value').removeData('value');
			$(this).parent().before(address_container);
			address_container.find('select[name="state_id[]"]').change();
			
			return false;
		});
		
		//Remove um endereço
		$('.address .remove').live('click', function(){
			var addresses_count = $('.address').length;
			
			if(addresses_count === 1){
				alert('O cliente deve possuir, pelo menos, 1 endereço cadastrado!');
				return false;
			}
			
			$(this).parents('.address:first').remove();
			return false;
		});
	});
</script>