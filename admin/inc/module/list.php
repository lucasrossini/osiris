<?php
	//Parâmetros
	$package = $sys_control->get_page(0);
	$module = $sys_control->get_page(1);
	
	//Redireciona para a página principal do módulo caso ele não possua lista de registros
	if(!System\System::has_list($package, $module))
		URL\URL::redirect('/admin/'.$package.'/'.$module.'/main');
	
	define('SEARCH_PARAM', 'search');
	
	//Função para montar a cláusula WHERE das tabelas de registros com busca
	function search_where_clause($fields = array()){
		$where_clause = 'TRUE';
		
		if(\HTTP\Request::get(SEARCH_PARAM)){
			$where_clause = '';
			
			foreach($fields as $field){
				$field = !strpos($field, '.') ? '`'.$field.'`' : $field;
				$where_clause .= $field.' LIKE "%'.\HTTP\Request::get(SEARCH_PARAM).'%" OR ';
			}
			
			$where_clause = '('.rtrim($where_clause, ' OR ').')';
		}
		
		return $where_clause;
	}
	
	//Função para montar a cláusula WHERE das tabelas de registros com filtro
	function filter_where_clause($filters = array(), $require_filter = false){
		$where_clause = 'TRUE';
		
		if(!\HTTP\Request::get(SEARCH_PARAM) && sizeof($filters)){
			foreach($filters as $field => $param){
				$param_value = \HTTP\Request::get($param);
				
				if(((string)$param_value !== '') || $require_filter){
					$field = !strpos($field, '.') ? '`'.$field.'`' : $field;
					$where_clause .= ' AND '.$field.' = '.(int)$param_value;
				}
			}
			
			$where_clause = '('.$where_clause.')';
		}
		
		return $where_clause;
	}
	
	//Palavras da seção na língua definida
	$lang = $sys_language->get('admin_records_list');
	
	echo '
		<div id="records-list">
			<h2>'.$lang['search'].'</h2>
			<div class="content">
	';
	
	//Formulário de pesquisa
	$search_form = new \Form\Form('form_search', 'get', array(SEARCH_PARAM => array('validation' => array('is_empty'))));
	$search_form->add_field(new \Form\TextInput(SEARCH_PARAM, $lang['search'], \HTTP\Request::get(SEARCH_PARAM), array('placeholder' => $lang['type_search_terms'])));
	$search_form->add_field(new \Form\Button('search_button', 'OK'));
	
	//Valida o formulário
	$search_form->validate();
	
	//Exibe o formulário
	$search_form->display(true, false, false, 'OK');
	
	echo '
		</div>
		
		<h2>
			'.$lang['records'].'
			<a href="admin/'.$package.'/'.$module.'/main" class="right new insert-record">'.$sys_language->get('class_form', 'insert_new_record').'</a>
		</h2>
		
		<div class="content">
	';
	
	//Pesquisa
	if(\HTTP\Request::get(SEARCH_PARAM)){
		echo '
			<p id="search-message">
				<strong>'.$lang['showing_search_results'].':</strong>
				<em>'.\HTTP\Request::get(SEARCH_PARAM).'</em>
				<a href="'.\URL\URL::remove_params(URL, array(SEARCH_PARAM)).'" class="all-records">'.$lang['show_all_records'].'</a>
			</p>
		';
	}
	
	//Inclui a lista de registros do módulo atual
	\System\System::include_module($package, $module, 'list');
	
	echo '
				</div>
			</div>
		</div>
	';
?>

<script>
	//Tipsy
	$(document).ready(function(){
		$('#records-list .default-table .action a:not(.disabled)').tipsy({gravity: 's', offset: 5});
	});
</script>