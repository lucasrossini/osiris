<?php
	//Campos do banco de dados para pesquisa
	function get_search_fields($class_name){
		$search_fields = array();
		
		switch($class_name){
			default:
				$search_fields['search_fields'] = array();
				$search_fields['extra_fields'] = array();
		}
		
		return $search_fields;
	}
	
	//Captura os valores necessários
	$is_search = \HTTP\Request::is_set('get', SiteSearch::SEARCH_PARAM);
	$query = \HTTP\Request::get(SiteSearch::SEARCH_PARAM);
	$adv = (int)\HTTP\Request::get('adv');
	$search_class = $sys_control->get_current_page_attr('search_class');
	
	if($adv){
		$adv_display = 'block';
		$adv_label_toggle_inactive = $sys_language->get('site_search', 'show');
		$adv_label_toggle_active = $sys_language->get('site_search', 'hide');
	}
	else{
		$adv_display = 'none';
		$adv_label_toggle_inactive = $sys_language->get('site_search', 'hide');
		$adv_label_toggle_active = $sys_language->get('site_search', 'show');
	}
	
	//Monta o formulário de pesquisa
	$search_form = new \Form\Form('form_search', '', array(), array(), '', null, '', false, 'get');
	$search_form->add_html('<h4>'.$sys_language->get('site_search', 'search_filters').'</h4>');
	$search_form->add_field('text', SiteSearch::SEARCH_PARAM);
	$search_form->add_hidden_field('adv', '', $adv);
	
	$search_form->add_html('<div class="advanced-search-container"><a href="#" class="toggle"><span rel="'.$adv_label_toggle_inactive.'">'.$adv_label_toggle_active.'</span> '.$sys_language->get('site_search', 'advanced_search').'</a><div class="inner" style="display:'.$adv_display.'">');
	
	if($search_class != 'SiteSearch'){
		$search_fields = get_search_fields($search_class);
		
		$extra_fields = $search_fields['extra_fields'];
		$search_fields = $search_fields['search_fields'];
	}
	
	//Campos de pesquisa avançada
	switch($search_class){
		case 'SiteSearch': //Todo o site
			$global_search_classes = array();
			
			if(\HTTP\Request::get('search_classes')){
				$pieces = explode(',', \HTTP\Request::get('search_classes'));
				
				foreach($pieces as $piece){
					$piece = trim($piece);
					
					if(!empty($piece))
						$global_search_classes[] = $piece;
				}
			}
			else{
				$site_class_folder = \Storage\Folder::scan('/class/dao/');
				$site_classes = array();
				
				foreach($site_class_folder->files as $class_file)
					$site_classes[] = \Storage\File::name($class_file);
				
				$parent = new ReflectionClass('\Database\DatabaseObject');
				
				foreach($site_classes as $site_class){
					$reflection_class = new ReflectionClass('\DAO\\'.$site_class);
					
					if($reflection_class->isSubclassOf($parent) && $reflection_class->hasMethod('prepare_search_results') && $reflection_class->hasConstant('SEARCHABLE'))
						$global_search_classes[] = $site_class;
				}
			}
			
			if(sizeof($global_search_classes)){
				foreach($global_search_classes as $global_search_class){
					$aux = get_search_fields($global_search_class);
					
					if(sizeof($aux)){
						$search_fields[$global_search_class] = $aux['search_fields'];
						$extra_fields[$global_search_class] = $aux['extra_fields'];
					}
				}
			}
			
			break;
	}
	
	$per_page_options = array(10 => '10 '.$sys_language->get('site_search', 'results'), 20 => '20 '.$sys_language->get('site_search', 'results'), 40 => '40 '.$sys_language->get('site_search', 'results'));
	$search_form->add_select('pp', $sys_language->get('site_search', 'results_per_page'), '', $per_page_options);
	$search_form->add_html('</div></div>');
	
	$search_form->add_button('', $sys_language->get('site_search', 'find'), 'submit', array('class' => 'button'));
	$search_form->display(true, false);
	
	if($is_search){
		//Realiza a pesquisa
		$search_result_array = $search_class::search($query, $search_fields, $extra_fields);
		
		$search_results = $search_result_array['results'];
		$global_total_records = $search_result_array['count'];
		
		//Corrige o vetor de resultados
		if($search_class == 'SiteSearch'){
			$aux = array();
			
			foreach($search_results as $class_name => $results)
				$aux = array_merge($aux, $results);
			
			$search_results = $aux;
		}
		
		//Faz a paginação
		$pp = (int)$search_form->get('pp');
		$records_per_page = (array_key_exists($pp, $per_page_options) && $adv) ? $pp : 10;
		
		$pagination = new Paginator($global_total_records, $records_per_page);
		$search_results = $pagination->get_paginated_array($search_results);
		$page_total_records = sizeof($search_results);
		
		//Corrige o vetor de resultados
		if($search_class == 'SiteSearch'){
			$aux = array();
			
			foreach($search_results as $result)
				$aux[get_class($result)][] = $result;
			
			$search_results = $aux;
		}
		
		//Exibe os resultados da pesquisa
		$search_message_complement = !empty($query) ? ' '.$sys_language->get('site_search', 'for_query').' "<em>'.$query.'</em>"' : '';
		
		echo '
			<div class="search-results-record-count">
				'.sprintf($sys_language->get('site_search', 'showing_results'), '<strong>'.$page_total_records.'</strong>', '<strong>'.$global_total_records.'</strong>').$search_message_complement.':
				<a href="'.URL_WITHOUT_GETS.'" class="clear-filters">'.$sys_language->get('site_search', 'clear_filters').'</a>
			</div>
		';
		
		if(sizeof($search_results)){
			echo '
				<div class="search-results-container">
					'.$search_class::prepare_search_results($search_results, false).'
					
					<div class="clear"></div>
				</div>
			';
			
			//Exibe a paginação
			$pagination->display_pages();
		}
		else{
			echo '<div class="no-results">'.$sys_language->get('site_search', 'no_results').'</div>';
		}
	}
	else{
		echo '<p class="text">'.$sys_language->get('site_search', 'description').'</p>';
	}
?>

<script>
	//Pesquisa avançada
	if(!parseInt($('#adv').val()))
		$('.advanced-search-container .inner input, .advanced-search-container .inner select').attr('disabled', true);
	
	$('.advanced-search-container .toggle').click(function(){
		$(this).parent().find('.inner input, .inner select').removeAttr('disabled');
		
		$(this).parent().find('.inner').slideToggle(400, function(){
			if(parseInt($('#adv').val()) > 0){
				adv_value = 0;
				$(this).parent().find('.inner input, .inner select').attr('disabled', true);
			}
			else{
				adv_value = 1;
			}
			
			$('#adv').val(adv_value);
		});
		
		var aux = $(this).find('span').html();
		$(this).find('span').html($(this).find('span').attr('rel'));
		$(this).find('span').attr('rel', aux);
		
		return false;
	});
</script>