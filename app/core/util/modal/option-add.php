<?php
	//Insere nova opção
	if(\HTTP\Request::is_set('get', array('a', 'ins', 'target'))){
		$action = (int)\HTTP\Request::get('a');
		$option_label = '';
		$exists = false;
		
		switch($action){
			case 1: //Adicionar nível de administração
				$option_label = \HTTP\Request::get('name');
			
				$exists_sql = 'SELECT COUNT(*) AS total FROM sys_admin_level WHERE name = "'.$option_label.'"';
				$insert_sql = 'INSERT INTO sys_admin_level (name, slug) VALUES ("'.$option_label.'", "'.\Formatter\String::slug($option_label).'")';
				
				$invalid = !\HTTP\Request::get('name');
				break;
			
			default:
				exit($sys_language->get('option_add', 'invaid_action'));
				break;
		}
		
		if(!empty($exists_sql)){
			$db->query($exists_sql);
			$exists = $db->result(0)->total;
		}
		
		if($exists){
			$result = array('success' => false, 'error' => $sys_language->get('option_add', 'already_error'));
		}
		elseif($invalid){
			$result = array('success' => false, 'error' => $sys_language->get('option_add', 'empty_error'));
		}
		else{
			if($inserted_id = $db->query($insert_sql)){
				$script = '
					var select = window.parent.parent.$("'.\HTTP\Request::get('target').'");
					select.append("<option value=\''.$inserted_id.'\' selected>'.$option_label.'</option>");

					//Ordena as opções alfabeticamente
					if(select.length > 0){
						var selected_option = select.val();
						var options = $("option", select);
						var option_values = [];

						var default_option = (select.find("option:first").val().toString() == "") ? select.find("option:first") : null;
						var c = 0;

						options.each(function(){
							if((!default_option && !c) || (c > 0)){
								option_values.push({
									val: $(this).val(),
									text: $(this).text()
								});
							}

							c++;
						});

						option_values.sort(function(a, b){
							if(a.text > b.text)
								return 1;
							else if(a.text == b.text)
								return 0;
							else
								return -1;
						});

						select.find("option").remove();

						if(default_option)
							select.append(default_option);

						for(var i = 0, l = option_values.length; i < l; i++)
							select.append("<option value=\'" + option_values[i].val + "\'>" + option_values[i].text + "</option>");

						select.val(selected_option);
					}

					setTimeout(function(){
						window.parent.parent.$.fancybox.close();
					}, 1000);
				';
				
				$result = array('success' => true, 'message' => $sys_language->get('option_add', 'success_message'), 'script' => $script);
			}
			else{
				$result = array('success' => false, 'error' => $sys_language->get('option_add', 'error_message'));
			}
		}
		
		ob_end_clean();
		ob_start('ob_gzhandler');
		header('Content-type: application/json');
		echo json_encode($result);
		
		exit;
	}
	else{
		//Valida os parâmetros
		if(!\HTTP\Request::is_set('get', array('a', 'target', 'title')))
			exit($sys_language->get('common', 'invalid_params'));
	}
	
	echo '
		<div id="option-add-container">
			<header id="header">
				<hgroup>
					<h1><span class="icon">'.\HTTP\Request::get('title').'</span></h1>
					<h2>'.$sys_language->get('option_add', 'description').'</h2>
				</hgroup>
			</header>
	';
	
	//Cria formulário
	$form = new \Form\Form('form_option_add');
	
	switch(\HTTP\Request::get('a')){
		case 1: //Adicionar nível de administração
			$form->add_field(new \Form\TextInput('name', $sys_language->get('option_add', 'name')));
			break;
		
		default:
			exit($sys_language->get('option_add', 'invaid_action'));
			break;
	}
	
	$form->add_html('<div class="button-container">');
	$form->add_field(new \Form\Button('dialog_insert_button', $sys_language->get('option_add', 'insert'), 1, array('class' => 'small'), 'button'));
	$form->add_field(new \Form\Button('dialog_cancel_button', $sys_language->get('common', 'cancel'), 1, array('class' => 'small cancel'), 'button'));
	$form->add_html('</div>');
	
	//Exibe o formulário
	$form->display(true, false);
	
	echo '</div>';
?>

<script>
	//Inserir
	$('#dialog_insert_button').click(function(){
		var self = $(this);
		
		Ajax.toggle_loader(true);
		self.attr('disabled', true);
		
		$.getJSON('app/core/util/modal/wrapper?page=option-add&ins=1&a=<?php echo \HTTP\Request::get('a') ?>&target=<?php echo \HTTP\Request::get('target', false) ?>&' + $('#form_option_add').serialize(), function(response){
			if(response.success){
				$('.modal').append('<script>' + response.script + '<\/script>');
				Ajax.result_message('success', response.message);
			}
			else{
				Ajax.result_message('error', response.error);
			}
			
			self.removeAttr('disabled');
			Ajax.toggle_loader(false);
		});
	});
	
	$('#option-add-container input').keypress(function(e){
		if(e.which == 13){
			$('#dialog_insert_button').click();
			return false;
		}
	});

	//Cancelar
	$('#dialog_cancel_button').click(function(){
		window.parent.parent.$.fancybox.close();
	});
</script>