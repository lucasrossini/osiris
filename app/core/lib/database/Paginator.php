<?php
	namespace Database;
	
	/**
	 * Classe para paginação de registros.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 10/03/2014
	*/
	
	class Paginator{
		const ITEMS_PER_PAGE = 10;
		
		private $items_per_page;
		private $items_total;
		private $num_links;
		private $current_page;
		private $num_pages;
		private $mid_range;
		private $low;
		private $limit;
		private $return;
		private $querystring;
		private $page_param;
		
		/**
		 * Instancia um objeto de paginação.
		 * 
		 * @param int $items_total Quantidade total de itens da paginação.
		 * @param int $per_page Quantidade de itens por página.
		 * @param int $range Alcance de páginas próximas da atual a serem exibidas na lista de páginas.
		 * @param int $num_links Quantidade máxima de links de paginação a serem exibidos na lista de páginas.
		 * @param string $page_param Nome do parâmetro GET que controla a paginação.
		 */
		public function __construct($items_total = 0, $per_page = 10, $range = 5, $num_links = 5, $page_param = 'page'){
			$this->current_page = 1;
			$this->mid_range = $range;
			$this->items_per_page = (!is_numeric($per_page) || ($per_page <= 0)) ? self::ITEMS_PER_PAGE : $per_page;
			$this->items_total = $items_total;
			$this->num_links = $num_links;
			$this->page_param = $page_param;
			
			if($items_total)
				$this->paginate();
		}
		
		/**
		 * Realiza a paginação.
		 */
		private function paginate(){
			global $sys_language;
			
			//Calcula as páginas
			$this->num_pages = ceil($this->items_total / $this->items_per_page);
			$this->current_page = (int)$_GET[$this->page_param];
			
			if(($this->current_page < 1) || !is_numeric($this->current_page))
				$this->current_page = 1;
			elseif($this->current_page > $this->num_pages)
				$this->current_page = $this->num_pages;
			
			$prev_page = $this->current_page - 1;
			$next_page = $this->current_page + 1;
			
			//Mantém os parâmetros GET
			if($_GET){
				$args = explode('&', $_SERVER['QUERY_STRING']);
				
				foreach($args as $arg){
					$keyval = explode('=', $arg);
					
					if(($keyval[0] != $this->page_param) && ($keyval[0] != 'ipp'))
						$this->querystring .= '&'.$arg;
				}
			}
			
			if($this->querystring == '&')
				$this->querystring = null;
			
			//Monta o HTML da paginação
			if($this->num_pages > $this->num_links){
				$this->return = (($this->current_page != 1) && ($this->items_total >= $this->num_links)) ? '<a target="_self" class="paginate prev" href="'.\URL\URL::add_params(URL, array($this->page_param => $prev_page)).'" title="'.$sys_language->get('class_paginator', 'go_to_previous_page').'">&laquo; '.$sys_language->get('class_paginator', 'previous').'</a> ' : '<a class="inactive prev">&laquo; '.$sys_language->get('class_paginator', 'previous').'</a> ';
	
				$this->start_range = $this->current_page - floor($this->mid_range / 2);
				$this->end_range = $this->current_page + floor($this->mid_range / 2);
	
				if($this->start_range <= 0){
					$this->end_range += abs($this->start_range) + 1;
					$this->start_range = 1;
				}
				
				if($this->end_range > $this->num_pages){
					$this->start_range -= ($this->end_range - $this->num_pages);
					$this->end_range = $this->num_pages;
				}
				
				$this->range = range($this->start_range, $this->end_range);
	
				for($i = 1; $i <= $this->num_pages; $i++){
					if(($this->range[0] > 2) && ($i == $this->range[0]))
						$this->return .= ' <span class="etc">...</span> ';
					
					if(($i == 1) || ($i == $this->num_pages) || in_array($i, $this->range))
						$this->return .= (($i == $this->current_page) && ($_GET[$this->page_param] != 'all')) ? '<a target="_self" title="'.sprintf($sys_language->get('class_paginator', 'go_to_page'), $i, $this->num_pages).'" class="current num">'.$i.'</a> ' : '<a target="_self" class="paginate num" title="'.sprintf($sys_language->get('class_paginator', 'go_to_page'), $i, $this->num_pages).'" href="'.\URL\URL::add_params(URL, array($this->page_param => $i)).'">'.$i.'</a> ';
					
					if(($this->range[$this->mid_range - 1] < ($this->num_pages - 1)) && ($i == $this->range[$this->mid_range - 1]))
						$this->return .= ' <span class="etc">...</span> ';
				}
				
				$this->return .= ((($this->current_page != $this->num_pages) && ($this->items_total >= $this->num_links)) && ($_GET[$this->page_param] != 'all')) ? '<a target="_self" class="paginate next" href="'.\URL\URL::add_params(URL, array($this->page_param => $next_page)).'" title="'.$sys_language->get('class_paginator', 'go_to_next_page').'">'.$sys_language->get('class_paginator', 'next').' &raquo;</a>' : '<a class="inactive next">'.$sys_language->get('class_paginator', 'next').' &raquo;</a>';
			}
			else{
				$this->return = ($this->current_page != 1) ? '<a target="_self" class="paginate prev" href="'.\URL\URL::add_params(URL, array($this->page_param => $prev_page)).'" title="'.$sys_language->get('class_paginator', 'go_to_previous_page').'">&laquo; '.$sys_language->get('class_paginator', 'previous').'</a> ' : '<a class="inactive prev">&laquo; '.$sys_language->get('class_paginator', 'previous').'</a> ';
				
				for($i = 1; $i <= $this->num_pages; $i++)
					$this->return .= ($i == $this->current_page) ? '<a target="_self" class="current num">'.$i.'</a> ' : '<a target="_self" title="'.sprintf($sys_language->get('class_paginator', 'go_to_page'), $i, $this->num_pages).'" class="paginate num" href="'.\URL\URL::add_params(URL, array($this->page_param => $i)).'">'.$i.'</a> ';
				
				$this->return .= (($this->current_page != $this->num_pages) && ($_GET[$this->page_param] != 'all')) ? '<a target="_self" class="paginate next" href="'.\URL\URL::add_params(URL, array($this->page_param => $next_page)).'" title="'.$sys_language->get('class_paginator', 'go_to_next_page').'">'.$sys_language->get('class_paginator', 'next').' &raquo;</a>' : '<a class="inactive next">'.$sys_language->get('class_paginator', 'next').' &raquo;</a>';
			}
			
			//Calcula a cláusula LIMIT da consulta SQL
			$this->low = ceil(($this->current_page - 1) * $this->items_per_page);
			$this->limit = ($_GET['ipp'] == 'all') ? '' : ' LIMIT '.$this->low.','.$this->items_per_page;
		}
		
		/**
		 * Carrega um vetor com todos os registros paginados.
		 * 
		 * @param array $array Vetor a ser paginado.
		 * @return array Vetor paginado.
		 */
		public function get_paginated_array($array){
			return array_slice($array, $this->low, $this->items_per_page);
		}
		
		/**
		 * Retorna a cláusula LIMIT da consulta SQL.
		 * 
		 * @return string Cláusula LIMIT da consulta SQL.
		 */
		public function get_limit(){
			return $this->limit;
		}
		
		/**
		 * Retorna a quantidade total de itens da paginação.
		 * 
		 * @return int Quantidade total de itens da paginação.
		 */
		public function get_items_total(){
			return $this->items_total;
		}
		
		/**
		 * Exibe um elemento SELECT para selecionar a quantidade de registros por página.
		 * 
		 * @return string HTML montado.
		 */
		public function display_items_per_page(){
			$items = '';
			$ipp_array = array('10' => 10, '25' => 25, '50' => 50, '100' => 100, 'Todos' => 'all');
			
			foreach($ipp_array as $ipp_opt => $ipp_opt_value)
				$items .= ($ipp_opt_value == $this->items_per_page) ? '<option selected value="'.$ipp_opt_value.'">'.$ipp_opt.'</option>' : '<option value="'.$ipp_opt_value.'">'.$ipp_opt.'</option>';
				
			return '<select class="paginate" onchange="window.location=\'?'.$this->page_param.'=1&ipp=\' + this[this.selectedIndex].value + '.$this->querystring.'; return false">'.$items.'</select>';
		}
		
		/**
		 * Exibe um elemento SELECT contendo a paginação.
		 * 
		 * @return string HTML montado.
		 */
		public function display_jump_menu(){
			$options = '';
			
			for($i = 1; $i <= $this->num_pages; $i++)
				$options .= ($i == $this->current_page) ? '<option value="'.$i.'" selected>'.$i.'</option>' : '<option value="'.$i.'">'.$i.'</option>';
			
			return '<select class="paginate" onchange="window.location=\'?'.$this->page_param.'=\' + this[this.selectedIndex].value + '.$this->querystring.'; return false">'.$options.'</select>';
		}
		
		/**
		 * Exibe a paginação através de links.
		 * 
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public function display_pages($echo = true){
			$html = '';
			
			if($this->num_pages > 1)
				$html = '<div class="pagination">'.$this->return.'</div>';
			
			if(!$echo)
				return $html;
			
			echo $html;
		}
	}
?>