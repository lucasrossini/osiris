<?php
	/**
	 * Classe para busca geral no site.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 28/11/2013
	*/
	
	class SiteSearch{
		const SEARCH_PARAM = 'q';
		
		/**
		 * Realiza uma pesquisa em todo o site.
		 * 
		 * @param string $query Palavras-chave da pesquisa.
		 * @param array $fields Vetor com os campos da tabela do banco de dados considerados pela pesquisa (LIKE).
		 * @param array $extra_fields Vetor com as cláusulas definidas na consulta SQL a ser realizada, onde a chave é o campo da tabela e o valor é o valor do campo da tabela.
		 * @param int $count Quantidade de registros a serem carregados (0 para todos).
		 * @param array $order_fields Vetor com as cláusulas de ordenação (ORDER BY) da consulta SQL a ser realizada, onde a chave é o campo a ser ordenado e o valor é o tipo de ordenação (ASC ou DESC).
		 * @param boolean $force_and Indica se as palavras-chave da pesquisa devem estar incluídas em todos os campos considerados da tabela do banco de dados. 
		 * @return array Vetor com os índices 'count', que contém o total de registros retornados; e 'results', que contém a lista de objetos resultantes.
		 */
		public static function search($query = '', $fields = array(), $extra_fields = array(), $count = 0, $order_fields = array(), $force_and = false){
			$objects = array();
			$total = 0;
			
			if(!empty($query) && sizeof($fields)){
				foreach($fields as $class_name => $search_fields){
					$dao_class = '\DAO\\'.$class_name;
					$results = $dao_class::search($query, $search_fields, $extra_fields[$class_name], $count, false, $order_fields, $force_and);
					
					$objects[$class_name] = $results['results'];
					$total += $results['count'];
				}
			}
			
			return array('results' => $objects, 'count' => $total);
		}
		
		/**
		 * Monta os resultados da pesquisa.
		 * 
		 * @param array $results Vetor multidimensional com os resultados da pesquisa geral no site, onde a chave é o nome da classe DAO que manipula a tabela do banco de dados e o valor é o vetor resultante da consulta SQL ao banco de dados.
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public static function prepare_search_results($results = array(), $echo = true){
			global $sys_language;
			$html = '';
			
			if(sizeof($results)){
				foreach($results as $class_name => $objects){
					if(sizeof($objects)){
						$class_name = '\DAO\\'.$class_name;
						
						$html .= '
							<div class="search-results-area">
								'.$class_name::get_constant('SEARCH_AREA').' &mdash; '.sizeof($objects).' '.$sys_language->get('class_site_search', 'result').'(s)
								<a href="'.$class_name::get_constant('SEARCH_PATH').'?'.self::SEARCH_PARAM.'='.urlencode(\HTTP\Request::get(self::SEARCH_PARAM)).'&form_search_submit=1" title="'.$sys_language->get('class_site_search', 'search_on').' &quot;'.$class_name::get_constant('SEARCH_AREA').'&quot;">'.$sys_language->get('class_site_search', 'search_on').' "'.$class_name::get_constant('SEARCH_AREA').'"</a>
							</div>
							
							<div class="search-results-list">
								'.$class_name::prepare_search_results($objects, false).'
								<div class="clear"></div>
							</div>
						';
					}
				}
			}
			
			if($echo)
				echo $html;
			else
				return $html;
		}
	}
?>