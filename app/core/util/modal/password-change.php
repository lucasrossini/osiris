<?php
	//Verifica a necessidade da senha anterior
	$require_previous = !\HTTP\Request::is_set('get', 'prev') ? true : (int)\HTTP\Request::get('prev');
	
	//Verifica a senha antiga e valida a nova senha
	if(\HTTP\Request::is_set('post', 'id')){
		$typed_old_password = \HTTP\Request::post('old_password');
		$typed_new_password = \HTTP\Request::post('new_password');
		$table = \HTTP\Request::post('table');
		$field = \HTTP\Request::post('field');
		$id = (int)\HTTP\Request::post('id');
		
		if($require_previous){
			$db->query('SELECT '.$field.' FROM '.$table.' WHERE id = '.$id);
			$old_password = \Security\Crypt::undo($db->result(0)->$field);
		}
		
		if($require_previous && ((string)$typed_old_password !== (string)$old_password)){
			$result = array('success' => false, 'error' => $sys_language->get('password_change', 'incorrect_password'));
		}
		elseif(empty($typed_new_password)){
			$result = array('success' => false, 'error' => $sys_language->get('password_change', 'empty_password_error'));
		}
		else{
			if(!$db->query('UPDATE '.$table.' SET '.$field.' = "'.\Security\Crypt::exec($typed_new_password).'" WHERE id = '.$id)){
				$result = array('success' => false, 'error' => $sys_language->get('password_change', 'change_password_error'));
			}
			else{
				$script = '
					setTimeout(function(){
						window.parent.parent.$.fancybox.close();
					}, 1000);
				';
				
				$result = array('success' => true, 'message' => $sys_language->get('password_change', 'password_change_success'), 'script' => $script);
			}
		}
		
		ob_end_clean();
		ob_start('ob_gzhandler');
		header('Content-type: application/json');
		echo json_encode($result);
		
		exit;
	}
	
	$lang_description = $require_previous ? 'description' : 'description_no_previous';
	
	echo '
		<div id="password-change-container">
			<header id="header">
				<hgroup>
					<h1><span class="icon">'.$sys_language->get('password_change', 'change_password').'</span></h1>
					<h2>'.$sys_language->get('password_change', $lang_description).'</h2>
				</hgroup>
			</header>
	';
	
	//Cria formulário
	$form = new \Form\Form('form_password_change');
	
	//Cria os campos do formulário
	if($require_previous)
		$form->add_field(new \Form\Password('old_password', $sys_language->get('password_change', 'old_password'), '', array(), false));
	
	$form->add_field(new \Form\Password('new_password', $sys_language->get('password_change', 'new_password'), '', array(), false));
	$form->add_field(new \Form\Hidden('table', '', \HTTP\Request::get('table')));
	$form->add_field(new \Form\Hidden('field', '', \HTTP\Request::get('field')));
	$form->add_field(new \Form\Hidden('id', '', \HTTP\Request::get('id')));
	
	$form->add_html('<div class="button-container">');
	$form->add_field(new \Form\Button('check_password_button', $sys_language->get('password_change', 'redefine_password'), 1, array('class' => 'small'), 'button'));
	$form->add_field(new \Form\Button('dialog_cancel_button', $sys_language->get('common', 'cancel'), 1, array('class' => 'small cancel'), 'button'));
	$form->add_html('</div>');
	
	//Exibe o formulário
	$form->display(true, false);
	
	echo '</div>';
?>

<script>
	//Verifica e altera senha
	$('#check_password_button').click(function(){
		var self = $(this);
		
		Ajax.toggle_loader(true);
		self.attr('disabled', true);
		
		$.post('app/core/util/modal/wrapper?page=password-change&prev=' + <?php echo \HTTP\Request::get('prev') ?>, {table: $('#table').val(), field: $('#field').val(), id: $('#id').val(), old_password: $('#old_password').val(), new_password: $('#new_password').val()}, function(response){
			if(response.success){
				$('.modal').append('<script>' + response.script + '<\/script>');
				Ajax.result_message('success', response.message);
			}
			else{
				Ajax.result_message('error', response.error);
			}
			
			self.removeAttr('disabled');
			Ajax.toggle_loader(false);
		}, 'json');
	});
	
	$('#old_password, #new_password').keypress(function(e){
		if(e.which == 13){
			$('#check_password_button').click();
			return false;
		}
	});
	
	//Cancelar
	$('#dialog_cancel_button').click(function(){
		window.parent.parent.$.fancybox.close();
	});
</script>