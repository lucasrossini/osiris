<?php
	namespace Util;
	
	/**
	 * Classe para geração de tabelas de registros.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 11/03/2014
	*/
	
	class Table{
		const SORT_ASC = 'asc';
		const SORT_DESC = 'desc';
		
		private $name;
		
		private $items;
		private $items_total;
		private $fields;
		private $checkbox_column;
		
		private $sortable = false;
		private $sort_field = '';
		private $sort_type = '';
		private $sort_params;
		
		private $sql;
		private $per_page;
		private $paginator;
		
		/**
		 * Instancia um objeto de tabela.
		 * 
		 * @param string $name ID da tabela.
		 * @param string $sql Consulta SQL a ser realizada para carregar os registros da tabela.
		 * @param array $fields Vetor multidimensional com as configurações dos campos a serem exibidos pela tabela, onde a chave é o nome do campo no banco de dados e o valor é um vetor com os índices 'name', que indica o nome a ser exibido para o campo no cabeçalho da tabela; 'class', que indica a classe CSS a ser aplicada em um elemento SPAN que envolve o valor do campo na tabela; 'type', que indica o tipo de formatação a ser realizado sobre o valor do campo; e 'info', que indica o texto a ser exibido abaixo do valor do campo na tabela (obs.: os nomes dos campos da tabela do banco de dados que forem colocados entre colchetes nesse texto serão substituídos pelos seus respectivos valores).
		 * @param int $per_page Quantidade de registros por página a serem exibidos pela tabela (0 para exibir todos).
		 * @param boolean $checkbox_column Define se a tabela deve conter uma coluna de checkboxes para marcação das linhas.
		 * @param boolean $has_view Define se o ícone de visualização dos registros deve ser exibido na tabela.
		 * @param boolean $has_edit Define se o ícone de edição dos registros deve ser exibido na tabela.
		 * @param boolean $has_delete Define se o ícone de remoção dos registros deve ser exibido na tabela.
		 */
		public function __construct($name, $sql, $fields = array(), $per_page = 10, $checkbox_column = true, $has_view = true, $has_edit = true, $has_delete = true){
			$this->name = $name;
			$this->sql = $sql;
			$this->fields = $fields;
			$this->per_page = $per_page;
			$this->checkbox_column = $checkbox_column;
			$this->has_view = $has_view;
			$this->has_edit = $has_edit;
			$this->has_delete = $has_delete;
			$this->sort_params = array('field' => $this->name.'_sort_field', 'type' => $this->name.'_sort_type');
		}
		
		/**
		 * Define os parâmetros de ordenação da tabela.
		 * 
		 * @param string $field Nome do campo que deve ser ordenado.
		 * @param string $type Tipo de ordenação a ser realizada, que pode ser SORT_ASC para crescente ou SORT_DESC para descrescente.
		 */
		public function sort($field = '', $type = self::SORT_ASC){
			if(\HTTP\Request::is_set('get', array($this->sort_params['field'], $this->sort_params['type']))){
				$field = \HTTP\Request::get($this->sort_params['field']);
				$type = \HTTP\Request::get($this->sort_params['type']);
			}
			
			$this->sortable = true;
			$this->sort_field = $field;
			$this->sort_type = strtolower($type);
		}
		
		/**
		 * Exibe a tabela.
		 * 
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @param boolean $paginate Define se os registros da tabela devem ser paginados.
		 * @param boolean $show_records_count Define se deve ser exibida a contagem de registros da tabela.
		 * @param string $class Classe CSS a ser atribuída à tabela.
		 * @param string $empty_message Mensagem a ser exibida caso a tabela não possua nenhum registro.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public function display($echo = true, $paginate = true, $show_records_count = true, $class = 'default-table', $empty_message = ''){
			global $db, $sys_language, $sys_user, $sys_control;
			
			//Monta cláusula de ordenação dos registros
			if($this->sortable && $this->sort_field)
				$this->sql .= ' ORDER BY `'.$this->sort_field.'` '.strtoupper($this->sort_type);
			
			//Carrega os registros
			$db->query('SELECT COUNT(*) AS total FROM ('.$this->sql.') AS sub');
			$this->items_total = $db->result(0)->total;
			
			if($paginate && $this->per_page){
				$this->paginator = new \Database\Paginator($this->items_total, $this->per_page, 5, 5, $this->name.'_page');
				$this->sql .= $this->paginator->get_limit();
			}
			
			$db->query($this->sql, 'array');
			$this->items = $db->result();
			
			//Total de registros
			if($show_records_count)
				$this->html .= '<p class="record-count">'.sprintf($sys_language->get('class_table', 'record_count'), '<strong class="num">'.$this->items_total.'</strong>').':</p>';
			
			$this->html .= '
				<table class="'.$class.'" id="'.$this->name.'">
					<tr>
			';
			
			//Colunas
			if($this->checkbox_column)
				$this->html .= '<th class="checkbox"><input type="checkbox" class="check-all" /></th>';
			
			foreach($this->fields as $field => $field_attr){
				if($this->sortable){
					if($field == $this->sort_field){
						$sort_class = $this->sort_type;
						$sort_type = ($this->sort_type == self::SORT_ASC) ? self::SORT_DESC : self::SORT_ASC;
					}
					else{
						$sort_class = '';
						$sort_type = self::SORT_ASC;
					}
					
					$sort_title = $sys_language->get('class_table', 'order_by').' &quot;'.$field_attr['name'].'&quot; ('.$sys_language->get('class_table', $sort_type).')';
					$sorted_url = \URL\URL::add_params(URL, array($this->sort_params['field'] => $field, $this->sort_params['type'] => $sort_type));
					
					$this->html .= '<th><a href="'.$sorted_url.'" title="'.$sort_title.'" class="sort '.$sort_class.'">'.$field_attr['name'].'</a></th>';
				}
				else{
					$this->html .= '<th>'.$field_attr['name'].'</th>';
				}
			}
			
			//Campos de ação
			$current_package = $sys_control->get_page(0);
			$current_module = $sys_control->get_page(1);
			
			$module_permissions = $sys_user->get_module_data($current_package, $current_module);
			$colspan = (int)($this->has_view && $module_permissions['can_view']) + (int)($this->has_edit && $module_permissions['can_edit']) + (int)($this->has_delete && $module_permissions['can_delete']);
			
			if($colspan)
				$this->html .= '<th colspan="'.$colspan.'" class="actions">'.$sys_language->get('class_table', 'action').'</th>';
			
			$this->html .= '</tr>';
			
			//Registros
			if($this->items_total){
				foreach($this->items as $item){
					$row_class = ((($this->has_edit && (\HTTP\Request::get('mode') == 'edit')) || ($this->has_view && (\HTTP\Request::get('mode') == 'view'))) && ((int)\HTTP\Request::get('id') === (int)$item['id'])) ? 'current' : '';
					$this->html .= '<tr class="'.$row_class.'" rel="'.$item['id'].'">';
					
					if($this->checkbox_column)
						$this->html .= '<td class="checkbox"><input type="checkbox" name="record[]" value="'.$item['id'].'" /></td>';
					
					foreach($this->fields as $field => $field_attr){
						$value = $item[$field];
						$aux = strip_tags($value);
						
						if((string)$value !== ''){
							$types_found = explode(',', $field_attr['type']);
							
							foreach($types_found as $type_found){
								$field_type = reset(explode('[', $type_found));
								
								switch($field_type){
									case 'money':
										$aux = (float)$aux;
										$formatted = \Formatter\Number::money($aux);
										break;
									
									case 'number':
										$aux = str_replace('.', ',', $aux);
										$formatted = number_format($aux, 0, '', '.');
										break;
									
									case 'date':
										$formatted = \DateTime\Date::convert($aux);
										break;
									
									case 'excerpt':
										$formatted = '<span title="'.$aux.'">'.\Formatter\String::truncate($aux, reset(\Util\Regex::extract_brackets($type_found)), '...', false).'</span>';
										break;
									
									case 'strip_tags':
										$formatted = $value = $aux;
										break;
									
									default:
										$formatted = $aux;
										break;
								}
							}
						}
						else{
							$formatted = '---';
						}
						
						$value = \UI\HTML::replace_tag_content($value, $formatted);
						
						//Campo de informação extra
						$info = '';
						$field_matches = array();
						
						if(!empty($field_attr['info'])){
							$info = $field_attr['info'];
							$field_matches = \Util\Regex::extract_brackets($info);
							
							if(sizeof($field_matches)){
								foreach($field_matches as $field_match)
									$info = str_replace('['.$field_match.']', $item[$field_match], $info);
							}
							
							$info = '<p>'.$info.'</p>';
						}
						
						$this->html .= '
							<td class="'.$field_attr['class'].'">
								'.$value.'
								'.$info.'
							</td>
						';
					}
					
					//Campos de ação
					$module_form_url = 'admin/'.$current_package.'/'.$current_module.'/main';
					
					$view_url = \URL\URL::add_params($module_form_url, array('mode' => 'view', 'id' => $item['id']));
					$edit_url = \URL\URL::add_params($module_form_url, array('mode' => 'edit', 'id' => $item['id']));
					$delete_url = \URL\URL::add_params($module_form_url, array('mode' => 'delete', 'id' => $item['id']));
					
					if($this->has_view && $module_permissions['can_view'])
						$this->html .= '<td class="action"><a href="'.$view_url.'" class="view" title="'.$sys_language->get('class_table', 'view').'" rel="'.$item['id'].'"><img src="admin/images/icons/view.png" alt="'.$sys_language->get('class_table', 'view').'"></a></td>';
					
					if($this->has_edit && $module_permissions['can_edit'])
						$this->html .= '<td class="action"><a href="'.$edit_url.'" class="edit" title="'.$sys_language->get('class_table', 'edit').'" rel="'.$item['id'].'"><img src="admin/images/icons/edit.png" alt="'.$sys_language->get('class_table', 'edit').'"></a></td>';
					
					if($this->has_delete && $module_permissions['can_delete'])
						$this->html .= '<td class="action"><a href="'.$delete_url.'" class="delete" title="'.$sys_language->get('class_table', 'delete').'" rel="'.$item['id'].'"><img src="admin/images/icons/delete.png" alt="'.$sys_language->get('class_table', 'delete').'"></a></td>';
					
					$this->html .= '</tr>';
				}
			}
			else{
				//Nenhum registro
				if(empty($empty_message))
					$empty_message = $sys_language->get('class_table', 'no_records');
				
				$this->html .= '<tr class="empty"><td colspan="'.(sizeof($this->fields) + 3).'" class="center"><span class="empty-message">'.$empty_message.'</span></td></tr>';
			}
			
			$this->html .= '</table>';
			
			//Marcação de linhas
			if($this->checkbox_column){
				$this->html .= '
					<script>
						//Marcação de linhas da tabela
						$("#'.$this->name.' tr th .check-all").click(function(){
							if($(this).is(":checked")){
								$("#'.$this->name.' td.checkbox > input").attr("checked", true);
								$("#'.$this->name.' tr:not(:first, .empty)").addClass("selected");
							}
							else{
								$("#'.$this->name.' td.checkbox > input").removeAttr("checked");
								$("#'.$this->name.' tr:not(:first, .empty)").removeClass("selected");
							}
						});
						
						$("#'.$this->name.' td.checkbox > input").click(function(e){
							if($(this).is(":checked"))
								$(this).parents("tr:first").addClass("selected");
							else
								$(this).parents("tr:first").removeClass("selected");
							
							e.stopPropagation();
						});
						
						$("#'.$this->name.' tr:not(:first, .empty)").click(function(){
							var checkbox = $(this).find("td.checkbox > input");
							
							if(checkbox.is(":checked")){
								checkbox.removeAttr("checked");
								$(this).removeClass("selected");
							}
							else{
								checkbox.attr("checked", true);
								$(this).addClass("selected");
							}
						});
					</script>
				';
			}
			
			//Paginação
			if($paginate && $this->per_page)
				$this->html .= $this->paginator->display_pages(false);
			
			//Exibe a tabela
			if($echo)
				echo $this->html;
			else
				return $this->html;
		}
	}
?>