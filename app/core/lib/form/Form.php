<?php
	namespace Form;
	
	/**
	 * Classe para criação de formulários.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 28/04/2014
	*/
	
	class Form{
		const FORCE_POST = 'force_post_variables';
		
		private $name;
		private $method;
		private $attributes;
		
		private $db_table = '';
		private $db_fields = array();
		private $db_relationships = array();
		private $dao_class = '';
		private $mode = 'insert';
		private $record_id;
		private $process_id;
		private $related_process_ids = array();
		
		private $html = '';
		private $script = '';
		private $mode_bar_html = '';
		private $tabs = array();
		
		private $submit_name;
		private $valid = true;
		
		private $fields = array();
		private $loaded_values = array();
		private $required_fields = array();
		
		/**
		 * Instancia objeto de formulário.
		 * 
		 * @param string $name Nome/ID do elemento FORM.
		 * @param string $method Método do formulário, que pode ser 'get' ou 'post'.
		 * @param array $attributes Vetor de atributos do elemento FORM, onde a chave é o nome do atributo e o valor é o valor do atributo.
		 */
		public function __construct($name = '', $method = 'post', $attributes = array()){
			$this->name = $name;
			$this->attributes = $attributes;
			$this->submit_name = $name.'_submit';
			
			$method = strtolower($method);
			$this->method = in_array($method, array('get', 'post')) ? $method : 'post';
		}
		
		/**
		 * Define as opções para o formulário trabalhar com o banco de dados.
		 * 
		 * @param string $table Tabela do banco de dados o qual o formulário está associado.
		 * @param array $fields Vetor multidimensional com os campos do formulário/tabela a serem utilizados na consulta SQL, onde o índice 'name' indica o nome do campo; o índice 'type' indica o tipo de formatação a ser realizada sobre o valor do campo; o índice 'related' define se o campo pertence a uma tabela relacionada; o índice 'table' indica o nome da tabela relacionada, caso exista; e o índice 'value' indica um valor fixo para o campo.
		 * @param int $record_id ID do registro da tabela do banco de dados associado.
		 * @param array $relationships Vetor onde o índice é o nome da tabela relacionada e o valor é um vetor com opções sobre o relacionamento, que pode ser 'foreign_key', que indica o nome do campo de chave estrangeira da tabela relacionada; 'mode', que indica a ação a ser realizada com os registros relacionados, podendo ser 'insert' para inserir novos ou 'update' para atualizar os já existentes; 'delete_before', que indica se os registros relacionados devem ser apagados antes de uma nova inserção; e 'ignore', que indica um vetor com os IDs dos processos que devem ignorar as transações de relacionamento.
		 * @param string $dao_class Nome da classe DAO relacionada à tabela do banco de dados associada ao formulário.
		 */
		public function set_database_options($table, $fields, $record_id = null, $relationships = array(), $dao_class = ''){
			global $db;
			
			$this->db_table = $table;
			$this->db_fields = $fields;
			$this->db_relationships = $relationships;
			$this->dao_class = $dao_class;
			$this->record_id = $record_id;
			
			//Carrega os IDs dos relacionamentos
			foreach($this->db_relationships as $related_table => $related_options){
				$foreign_record_id = !empty($related_options['foreign_key_source']) ? $this->related_process_ids[$related_options['foreign_key_source']][0] : $this->record_id;
				
				if($foreign_record_id){
					$db->query('SELECT id FROM '.$related_table.' WHERE `'.$related_options['foreign_key'].'` = '.$foreign_record_id.' LIMIT 0,1');
					$this->related_process_ids[$related_table][] = $db->result(0)->id;
				}
			}
			
			if(!$this->record_id)
				$this->set_mode('insert');
			
			//Carrega o registro
			$this->load();
		}
		
		/**
		 * Define o modo do formulário.
		 * 
		 * @param string $mode Modo de funcionamento do formulário, que pode ser 'insert', para inserção de registros; 'edit', para edição de registros; 'view', para visualização de registros; e 'delete', para remoção de registros.
		 * @param boolean $show_bar Define se deve ser exibida a barra que indica qual o modo do formulário no momento (somente nos modos 'edit' e 'view').
		 */
		public function set_mode($mode, $show_bar = true){
			global $sys_language;
			
			$mode = strtolower($mode);
			$this->mode = in_array($mode, array('insert', 'edit', 'view', 'delete')) ? $mode : 'insert';
			$this->mode_bar_html = '';
			$this->attributes['class'] .= ' '.$this->mode.'-mode';
			
			//Barra de modo do formulário
			if($show_bar && in_array($this->mode, array('edit', 'view'))){
				switch($this->mode){
					case 'edit':
						$info = $sys_language->get('class_form', 'edit_mode');
						break;
					
					case 'view':
						$info = $sys_language->get('class_form', 'view_mode');
						break;
				}
				
				$this->mode_bar_html = '
					<div class="mode-bar '.$this->mode.'">
						<p class="info">'.$info.'</p>
						<a href="'.\URL\URL::remove_params(URL, array('mode', 'id')).'" class="insert-link icon add">'.$sys_language->get('class_form', 'insert_new_record').'</a>

						<div class="clear"></div>
					</div>
				';
			}
		}
		
		/**
		 * Carrega o modo atual do formulário.
		 * 
		 * @return string Modo do formulário.
		 */
		public function get_mode(){
			return $this->mode;
		}
		
		/**
		 * Carrega o método utilizado pelo formulário.
		 * 
		 * @return string Método utilizado.
		 */
		public function get_method(){
			return $this->method;
		}
		
		/**
		 * Carrega a tabela do banco de dados associada ao formulário.
		 * 
		 * @return string Nome da tabela.
		 */
		public function get_table(){
			return $this->db_table;
		}
		
		/**
		 * Carrega um atributo do campo na lista de campos do formulário.
		 * 
		 * @param string $field Nome do campo.
		 * @param string $attr Nome do atributo.
		 * @return mixed Valor do atributo.
		 */
		public function get_field_attr($field, $attr){
			return $this->db_fields[$field][$attr];
		}
		
		/**
		 * Captura o valor de um campo do formulário caso ele tenha sido submetido ou possua valores carregados (modos 'edit', 'view' e 'delete').
		 * 
		 * @param string $var Nome do campo.
		 * @param string $field_type Tipo de campo.
		 * @param string $index Índice do item caso o valor retornado do campo seja um vetor.
		 * @param boolean $isset Define se apenas deve ser verificado se o campo foi enviado.
		 * @return string Valor carregado.
		 */
		public function get($var, $field_type = '', $index = null, $isset = false){
			if($this->is_submitted() || \HTTP\Request::post(self::FORCE_POST)){
				if($var == 'id'){
					$var = $isset ? true : $this->record_id;
				}
				elseif($this->method == 'post'){
					if($isset)
						$var = \HTTP\Request::is_set('post', $var);
					else
						$var = !in_array($field_type, array('editor', 'password')) ? \HTTP\Request::post($var) : \HTTP\Request::post($var, false);
				}
				elseif($this->method == 'get'){
					$var = $isset ? \HTTP\Request::is_set('get', $var) : \HTTP\Request::get($var);
				}
				else{
					$var = $isset ? false : '';
				}
			}
			else{
				$var = $this->loaded_values[$var];
				
				if($isset)
					$var = !empty($var);
			}
			
			if(!$isset && is_array($var) && !is_null($index))
				$var = $var[$index];
			
			return $var;
		}
		
		/**
		 * Adiciona um rótulo.
		 * 
		 * @param string $label Texto do rótulo.
		 * @param string $for Nome do campo ao qual faz referência.
		 * @param string $content Conteúdo do rótulo.
		 */
		public function add_label($label, $for = '', $content = ''){
			if(!$for)
				$for = 'undefined';
			
			$this->html .= empty($content) ? '<label rel="'.$for.'" class="label text-only" id="label-'.$for.'"><span class="label-title">'.$label.'</span></label>' : '<div class="label text-only" id="label-'.$for.'"><span class="label-title">'.$label.'</span>'.$content.'</div>';
		}
		
		/**
		 * Inicia o conteúdo de uma nova aba.
		 * 
		 * @param string $id ID da aba.
		 * @param string $label Rótulo da aba.
		 */
		public function init_tab($id, $label){
			$this->tabs[$id] = $label;
			$this->html .= '<div class="tab-content" data-id="'.$id.'">';
		}
		
		/**
		 * Fecha o conteúdo de uma aba.
		 */
		public function end_tab(){
			$this->html .= '</div>';
		}
		
		/**
		 * Adiciona um link para inserir uma nova opção a um elemento SELECT.
		 * 
		 * @param string $name ID do link.
		 * @param string $target Seletor jQuery do elemento SELECT alvo.
		 * @param int $action Identificador da ação a ser realizada pelo arquivo AJAX '/app/core/util/option-add'.
		 * @param array $params Vetor que indica os parâmetros GET a serem passados para a página (ex.: array('key' => 'value') se torna '&key=value').
		 * @param string $label Texto do link.
		 * @param boolean $after_select Define se o link deve ser exibido imediatamente após o elemento SELECT alvo ou somente no momento da chamada do método.
		 */
		public function add_select_appender($name, $target, $action, $params = array(), $label = '', $after_select = true){
			if(in_array($this->mode, array('insert', 'edit'))){
				global $sys_assets, $sys_language;
				
				if(empty($label))
					$label = $sys_language->get('class_form', 'insert_new_option');
				
				//Carrega os recursos necessários
				$sys_assets->load('css', 'app/assets/js/jquery/plugins/fancybox/jquery.fancybox.css');
				$sys_assets->load('js', 'app/assets/js/jquery/plugins/fancybox/jquery.fancybox.pack.js', array('charset' => 'ISO-8859-1'));
				
				$html = '<a href="#" id="'.$name.'" class="icon select-appender">'.$label.'</a>';
				
				$this->script .= '
					//Janela de adição de opção
					$("#'.$name.'").live("click", function(){
						$.fancybox({
							href: "app/core/util/modal/wrapper?page=option-add&title='.urlencode($label).'&target='.urlencode($target).'&a='.(int)$action.\Util\ArrayUtil::paramify($params, false).'",
							type: "iframe",
							width: 600,
							padding: 5,
							helpers: {
								overlay: {
									closeClick: false
								}
							}
						});

						return false;
					});
				';
				
				if($after_select)
					$this->script .= '$("'.$target.'").parent().append("'.str_replace('"', '\'', \Formatter\String::remove_line_breaks($html)).'");';
				else
					$this->html .= $html;
			}
		}
		
		/**
		 * Adiciona o campo à lista de campos do formulário.
		 * 
		 * @param \Form\Field $field Objeto do campo.
		 */
		public function add_to_list(Field $field){
			$field_id = $field->get('id');
			
			if(!isset($this->fields[$field_id]))
				$this->fields[$field_id] = array();
			
			$this->fields[$field_id][] = $field;
		}
		
		/**
		 * Adiciona um campo ao formulário.
		 * 
		 * @param \Form\Field $field Objeto do campo.
		 * @param string $tip Texto informativo exibido sobre o campo.
		 * @param string $label_complement Texto complementar do rótulo do campo.
		 * @param boolean $return Define se o HTML do campo deve ser retornado.
		 * @return string HTML do campo caso esteja definido que ele deve ser retornado.
		 */
		public function add_field(Field $field, $tip = '', $label_complement = '', $return = false){
			$field_id = $field->get('id');
			$index = sizeof((array)$this->fields[$field_id]);
			
			//Define atributos do campo
			if($label_complement)
				$field->set('label_complement', $label_complement);
			
			$field->set('form', $this);
			$field->set('tip', $tip);
			$field->set('multilang', $this->db_fields[$field_id]['multilang'] ? true : false);
			
			//Define o valor do campo
			if($this->is_submitted() || in_array($this->mode, array('edit', 'view', 'delete')))
				$field->set_value($this->get($field_id), $index);
			
			//Adiciona e renderiza o campo
			$this->add_to_list($field);
			$field->render();
			
			//HTML e script do campo
			$field_html = $field->get('html');
			$field_script = $field->get('script');
			
			//Retorna
			if($return){
				if($field_script)
					$field_html .= '<script>$(document).ready(function(){ '.$field_script.' });</script>';
				
				return $field_html;
			}
			
			//Adiciona HTML e script do campo ao formulário
			$this->html .= $field_html;
			$this->script .= $field_script;
		}
		
		/**
		 * Adiciona um conteúdo HTML ao formulário.
		 * 
		 * @param string $html Conteúdo HTML a ser adicionado.
		 */
		public function add_html($html){
			$this->html .= $html;
		}
		
		/**
		 * Verifica se o formulário foi enviado.
		 * 
		 * @return boolean TRUE caso o formulário tenha sido enviado e FALSE caso o formulário não tenha sido enviado.
		 */
		public function is_submitted(){
			return \HTTP\Request::is_set($this->method, $this->submit_name);
		}
		
		/**
		 * Verifica se o formulário foi enviado e nenhum erro de validação foi encontrado.
		 * 
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function is_success(){
			return ($this->is_submitted() && $this->valid);
		}
		
		/**
		 * Prepara os valores para inserção no banco de dados ou carregados do banco de dados.
		 * 
		 * @param string $field_name Nome do campo.
		 * @param string $value Valor a ser processado.
		 * @param string $type Tipo de processamento a ser realizado sobre o valor do campo.
		 * @param boolean $is_array Define se o campo é um vetor de dados, preparando cada valor separadamente.
		 * @param string $action Ação a ser realizada, podendo ser 'insert', 'edit' ou 'load' para, respectivamente, inserção, edição e carregamento de registros. 
		 * @return string Valor processado.
		 */
		private function prepare_value($field_name, $value, $type, $is_array = false, $action = 'insert'){
			global $db;
			
			//Opções
			$type_pieces = explode('[', $type);
			
			if(sizeof($type_pieces) > 1){
				$brackets = \Util\Regex::extract_brackets($type);
				$type = $type_pieces[0];
				
				switch($type){
					case 'slug':
						$related_fields = explode(',', $brackets[0]);
						$force_slug = ($brackets[1] == 'force');
						
						break;
				}
			}
			
			//Transforma um vetor de resultados do banco de dados em um vetor de valores de um campo
			if($is_array){
				if($action == 'load'){
					$values = array();

					foreach($value as $item){
						$key = reset(array_keys($item));
						$values[] = $item[$key];
					}
				}
				else{
					$values = is_array($value) ? $value : array($value);
				}
			}
			else{
				$values = array($value);
			}
			
			foreach($values as $key => $value){
				if(!in_array($type, array('editor', 'password')) && ($action != 'load'))
					\Security\Sanitizer::sanitize($value);
				
				switch($type){
					case 'password': //Criptografa ao inserir/editar e decriptografa ao carregar
						if($action == 'insert' || $action == 'edit')
							$value = \Security\Crypt::exec($value);
						elseif($action == 'load')
							$value = \Security\Crypt::undo($value);

						break;

					case 'slug': //Gera slug de um campo relacionado
						if($action == 'insert' || $action == 'edit'){
							$value = $original_slug = '';

							foreach($related_fields as $related_field)
								$original_slug .= \Formatter\String::slug(\Security\Sanitizer::restore($this->get(trim($related_field)))).'-';

							$original_slug = rtrim($original_slug, '-');
							$value = $original_slug;

							if(!$force_slug){
								$valid = false;
								$counter = 1;

								while(!$valid){
									$where_clause = ($action == 'edit') ? ' AND id != '.$this->record_id : '';
									$db->query('SELECT COUNT(*) AS total FROM '.$this->db_table.' WHERE `'.$field_name.'` = "'.$value.'"'.$where_clause);

									if($db->result(0)->total){
										$counter++;
										$value = $original_slug.'-'.$counter;
									}
									else{
										$valid = true;
									}
								}
							}
						}

						break;

					case 'editor': //Prepara os valores para um campo editor (remove espaços em branco extras e tags vazias)
						if($action == 'insert' || $action == 'edit')
							$value = addslashes(\UI\HTML::strip_empty_tags(str_replace('&nbsp;', '', $value)));
						elseif($action == 'load')
							$value = stripslashes($value);

						break;

					case 'date': //Converte data do formato '00/00/0000' para '0000-00-00' ao inserir/editar e o inverso ao carregar
						$value = !empty($value) ? \DateTime\Date::convert($value) : '';

						break;

					case 'time': //Converte hora do formato '00:00' para '00:00:00' ao inserir/editar e o inverso ao carregar
						if($action == 'insert' || $action == 'edit')
							$value = !empty($value) ? \DateTime\Time::time2sql($value) : '';
						elseif($action == 'load')
							$value = !empty($value) ? \DateTime\Time::sql2time($value) : '';

						break;

					case 'curdate': //Coloca a data atual ao inserir
						if($action == 'insert'){
							$value = date('Y-m-d');
						}
						elseif($action == 'edit'){
							$db->query('SELECT `'.$field_name.'` FROM '.$this->db_table.' WHERE id = '.$this->record_id);
							$value = $db->result(0)->$field_name;
						}

						break;

					case 'curtime': //Coloca a hora atual ao inserir
						if($action == 'insert'){
							$value = date('H:i:s');
						}
						elseif($action == 'edit'){
							$db->query('SELECT `'.$field_name.'` FROM '.$this->db_table.' WHERE id = '.$this->record_id);
							$value = $db->result(0)->$field_name;
						}

						break;

					case 'update_curdate': //Coloca a data atual ao editar
						if($action == 'insert')
							$value = '';
						elseif($action == 'edit')
							$value = date('Y-m-d');

						break;

					case 'update_curtime': //Coloca a hora atual ao editar
						if($action == 'insert')
							$value = '';
						elseif($action == 'edit')
							$value = date('H:i:s');

						break;

					case 'float': //Converte número do formato 0.000,00 para 0000.00 ao inserir/editar e o inverso ao carregar
						if($action == 'insert' || $action == 'edit')
							$value = \Formatter\Number::number2sql($value);
						elseif($action == 'load')
							$value = \Formatter\Number::sql2number($value);

						break;

					case 'serialize': //Serializa ao inserir/editar e desserializa ao carregar
						if($action == 'insert' || $action == 'edit')
							$value = addslashes(serialize($value));
						elseif($action == 'load')
							$value = unserialize($value);

						break;

					case 'int': //Faz um cast de valor inteiro
						if($action == 'insert' || $action == 'edit')
							$value = (int)$value;

						break;

					case 'boolean': //Transforma 0 em false e 1 (ou maior) em true ao carregar
						if($action == 'load')
							$value = $value ? true : false;

						break;

					case 'capitalize': //Transforma texto para o primeiro caracter de cada sentença em letra maiúscula e o restante minúsculo
						if($action == 'insert' || $action == 'edit')
							$value = ucwords(\Formatter\String::strtolower($value));

						break;

					case 'uppercase': //Transforma texto para todos os caracteres em letra maiúscula
						if($action == 'insert' || $action == 'edit'){
							$value = \Formatter\String::strtoupper($value);
							echo $value;
						}

						break;

					case 'lowercase': //Transforma texto para todos os caracteres em letra minúscula
						if($action == 'insert' || $action == 'edit')
							$value = \Formatter\String::strtolower($value);

						break;

					case 'encode': //Codifica conteúdo em UTF-8 ao inserir/editar e o inverso ao carregar
						if($action == 'insert' || $action == 'edit')
							$value = utf8_encode($value);
						elseif($action == 'load')
							$value = utf8_decode($value);

					default:
						if(is_array($value)) //Se for um vetor, captura o primeiro elemento
							$value = $value[0];

						break;
				}
				
				$values[$key] = $value;
			}
			
			return (!$is_array || ($is_array && $action != 'load')) ? $values[0] : $values;
		}
		
		/**
		 * Processa o formulário enviado inserindo ou atualizando registro na tabela associada do banco de dados.
		 * 
		 * @param boolean $refresh Define se a página deve ser recarregada após o processamento do formulário.
		 * @param array $after_queries Vetor com consultas SQL a serem realizadas após o processamento do registro. Se a string '%pid' estiver contida em alguma consulta SQL, será substituída pelo ID do processo.
		 * @param boolean $show_message Define se a mensagem de sucesso/erro será exibida após o processamento do formulário.
		 * @param array $messages Vetor multidimensional com as possíveis mensagens a serem exibidas, contendo os índices 'insert', que possui as mensagens após inserção de um registro; e 'edit', que possui as mensagens após atualização de um registro. O valor de ambos os índices é um vetor com os índices 'success', que contém a mensagem em caso de sucesso na operação; e 'error', que contém a mensagem em caso de falha na operação.
		 * @return int|boolean Resultado da consulta SQL executada, sendo o ID do registro inserido em caso de sucesso na inserção, TRUE em caso de sucesso na atualização ou FALSE em caso de falha na operação.
		 */
		public function process($refresh = true, $after_queries = array(), $show_message = true, $messages = array()){
			global $db, $sys_language;
			
			if(!$this->is_success() || !sizeof($this->db_fields))
				return false;
			
			//Mensagens
			if($show_message && !sizeof($messages)){
				$messages = array(
					'insert' => array(
						'success' => $sys_language->get('class_form', 'insert_success_message'),
						'error' => $sys_language->get('class_form', 'insert_error_message')
					),
					'edit' => array(
						'success' => $sys_language->get('class_form', 'edit_success_message'),
						'error' => $sys_language->get('class_form', 'edit_error_message')
					)
				);
			}
			
			$related_tables = $updated_primary_keys = array();
			
			//Monta a consulta SQL
			$sql_fields = $sql_values = '';
			
			foreach($this->db_fields as $field_name => $field_attr){
				if(!$field_attr['save'])
					continue;
				
				$i = 0;
				
				if(!$field_attr['related']){
					if($field_attr['value']){
						$is_null = false;
						$field_value = $field_attr['value'];
					}
					else{
						$is_null = ($db->field_null($this->db_table, $field_name) && ((string)$this->get($field_name, $field_attr['type']) === ''));
						$field_type = (!$field_attr['type'] && $field_attr['multilang']) ? 'serialize' : $field_attr['type'];
						$field_value = !$is_null ? $this->prepare_value($field_name, $this->get($field_name, $field_type), $field_type, $field_attr['is_array'], $this->mode) : '';
					}

					$sql_fields .= '`'.$field_name.'`, ';
					
					switch($this->mode){
						case 'insert':
							$sql_values .= $is_null ? 'NULL, ' : '"'.$field_value.'", ';
							break;
						
						case 'edit':
							if($field_attr['type'] != 'password')
								$sql_values .= $is_null ? '`'.$field_name.'` = NULL, ' : '`'.$field_name.'` = "'.$field_value.'", ';
							
							break;
					}
				}
				elseif(!empty($field_attr['table']) && !empty($this->db_relationships[$field_attr['table']]['foreign_key']) && !in_array($this->process_id, (array)$this->db_relationships[$field_attr['table']]['ignore'])){
					$field_values = $this->get($field_name, $field_attr['type']);
					$j = 0;
					
					if(!is_array($field_values))
						$field_values = array($field_values);
					
					foreach($field_values as $field_value){
						if($field_attr['value']){
							$is_null = false;
							$field_value = $field_attr['value'];
						}
						else{
							$is_null = ($db->field_null($field_attr['table'], $field_name) && ((string)$field_value === ''));
							$field_type = (!$field_attr['type'] && $field_attr['multilang']) ? 'serialize' : $field_attr['type'];
							$field_value = !$is_null ? $this->prepare_value($field_name, $field_value, $field_type, $field_attr['is_array'], $this->mode) : '';
						}
						
						$field_data = array('field' => $field_name, 'value' => $field_value, 'null' => $is_null);
						
						if($this->mode == 'edit'){
							$field_data['id'] = $this->db_relationships[$field_attr['table']]['primary_keys'][$j];
							
							if($field_data['id'])
								$updated_primary_keys[$field_attr['table']][$j] = $field_data['id'];
							
							$j++;
						}
						
						$related_tables[$field_attr['table']][$i++][] = $field_data;
					}
				}
			}
			
			foreach($updated_primary_keys as $table => $keys)
				$this->db_relationships[$table]['primary_keys'] = $keys;
			
			$sql_fields = rtrim($sql_fields, ', ');
			$sql_values = rtrim($sql_values, ', ');
			$transaction_success = false;
			
			try{
				$db->init_transaction();
				
				//Registro principal
				switch($this->mode){
					case 'insert':
						$query_result = $db->query('INSERT INTO '.$this->db_table.' ('.$sql_fields.') VALUES ('.$sql_values.')');
						$this->process_id = $query_result;
						
						break;
					
					case 'edit':
						if(!empty($sql_values))
							$query_result = $db->query('UPDATE '.$this->db_table.' SET '.$sql_values.' WHERE id = '.$this->record_id);
						
						$this->process_id = $this->record_id;
						break;
				}
				
				//Registros relacionados
				if(sizeof($related_tables) && !in_array($this->process_id, (array)$this->db_relationships[$field_attr['table']]['ignore'])){
					foreach($related_tables as $related_table => $related_rows){
						$process_id = !empty($this->db_relationships[$related_table]['foreign_key_source']) ? $this->related_process_ids[$this->db_relationships[$related_table]['foreign_key_source']][0] : $this->process_id;
						
						if(sizeof($this->db_relationships[$related_table]['primary_keys']))
							$db->query('DELETE FROM '.$related_table.' WHERE `'.$this->db_relationships[$related_table]['foreign_key'].'` = '.$process_id.' AND id NOT IN ('.implode(',', $this->db_relationships[$related_table]['primary_keys']).')');
						
						if(($this->mode == 'edit') && $this->db_relationships[$related_table]['delete_before'])
							$db->query('DELETE FROM '.$related_table.' WHERE `'.$this->db_relationships[$related_table]['foreign_key'].'` = '.$process_id);
						
						if($this->db_relationships[$related_table]['delete_before'])
							$related_mode = 'insert';
						else
							$related_mode = (in_array($this->mode, array('insert', 'edit')) && empty($this->db_relationships[$related_table]['mode'])) ? $this->mode : $this->db_relationships[$related_table]['mode'];
						
						foreach($related_rows as $related_row){
							$sql_fields = $sql_values = $related_sql = '';
							$blank = true;
							
							$current_related_mode = !$related_row[0]['id'] ? 'insert' : $related_mode;
							
							switch($current_related_mode){
								case 'insert':
									foreach($related_row as $related_field){
										$sql_fields .= '`'.$related_field['field'].'`, ';
										$sql_values .= ($related_field['null'] && empty($related_field['value'])) ? 'NULL, ' : '"'.$related_field['value'].'", ';

										if($blank && $related_field['value'])
											$blank = false;
									}

									if(!$blank)
										$related_sql = 'INSERT INTO '.$related_table.' (`'.$this->db_relationships[$related_table]['foreign_key'].'`, '.rtrim($sql_fields, ', ').') VALUES ('.$process_id.', '.rtrim($sql_values, ', ').')';
									
									break;
								
								case 'edit':
									foreach($related_row as $related_field){
										$sql_values .= ($related_field['null'] && empty($related_field['value'])) ? '`'.$related_field['field'].'` = NULL, ' : '`'.$related_field['field'].'` = "'.$related_field['value'].'", ';

										if($blank && $related_field['value'])
											$blank = false;
									}

									if(!$blank)
										$related_sql = 'UPDATE '.$related_table.' SET '.rtrim($sql_values, ', ').' WHERE id = '.$related_field['id']; //`'.$this->db_relationships[$related_table]['foreign_key'].'` = '.$process_id
									
									break;
							}
							
							//Captura IDs dos processos relacionados
							if($related_sql){
								$related_query_result = $db->query($related_sql);
								
								switch($related_mode){
									case 'insert':
										$related_process_id = $related_query_result;
										break;
									
									case 'edit':
										$db->query('SELECT id FROM '.$related_table.' WHERE `'.$this->db_relationships[$related_table]['foreign_key'].'` = '.$process_id.' LIMIT 0,1');
										$related_process_id = $db->result(0)->id;
										
										break;
								}

								$this->related_process_ids[$related_table][] = $related_process_id;
							}
						}
					}
				}
				
				//Consultas posteriores
				if(sizeof($after_queries)){
					array_walk($after_queries, function(&$value, $key, $process_id){
						$value = str_replace('%pid', $process_id, $value);
					}, $this->process_id);
					
					$db->multiple_query($after_queries);
				}
				
				$transaction_result = $db->end_transaction();
				$exception_message = $transaction_result['error'];
				$transaction_success = $transaction_result['success'];
			}
			catch(exception $e){
				$exception_message = $e->getMessage();
			}
			
			//Mensagens
			$has_error = false;
			
			if($show_message){
				if(empty($exception_message)){
					if(!empty($this->dao_class)){
						$reflection_class = new \ReflectionClass($this->dao_class);
						
						if($reflection_class->hasProperty('url')){
							$object = new $this->dao_class($this->process_id);
							$object_url = $object->get('url');
						}
					}
					
					$complement = $object_url ? '<div class="detail"><div class="inner"><a href="'.$object_url.'" target="_blank">'.$sys_language->get('class_form', 'check_record_link').' &raquo;</a></div></div>' : '';
					\UI\Message::success($messages[$this->mode]['success'].$complement);
				}
				else{
					$complement = !empty($exception_message) ? '<div class="detail"><div class="inner">'.$exception_message.'</div></div>' : '';
					\UI\Message::error($messages[$this->mode]['error'].$complement);
					$has_error = true;
				}
			}
			
			if($refresh){
				if($has_error){
					$post_data = $_POST;
					
					unset($post_data[$this->submit_name]);
					$post_data[self::FORCE_POST] = 1;
					
					\URL\URL::redirect_post(URL, $post_data);
				}
				else{
					\URL\URL::reload();
				}
			}
			
			return $transaction_success ? $query_result : false;
		}
		
		/**
		 * Processa o formulário enviado inserindo ou atualizando registro na tabela associada do banco de dados (através de AJAX).
		 * 
		 * @param array $after_queries Vetor com consultas SQL a serem realizadas após o processamento do registro. Se a string '%pid' estiver contida em alguma consulta SQL, será substituída pelo ID do processo.
		 * @param array $callbacks Vetor com os índices 'error', que contém o nome da função JavaScript a ser chamada após o processamento do formulário através de AJAX se um erro for encontrado; 'success', que contém o nome da função JavaScript a ser chamada após o processamento do formulário através de AJAX se tudo ocorreu corretamente; e 'default', que contém o nome da função JavaScript a ser chamada após o processamento do formulário através de AJAX independente do resultado.
		 * @param array $messages Vetor multidimensional com as possíveis mensagens a serem exibidas, contendo os índices 'insert', que possui as mensagens após inserção de um registro; e 'edit', que possui as mensagens após atualização de um registro. O valor de ambos os índices é um vetor com os índices 'success', que contém a mensagem em caso de sucesso na operação; e 'error', que contém a mensagem em caso de falha na operação.
		 */
		public function process_ajax($after_queries = array(), $callbacks = array('error' => 'ajax_form_error', 'success' => 'ajax_form_success', 'default' => 'ajax_form_callback'), $messages = array()){
			global $sys_assets, $sys_language;
			
			//Mensagens
			if(!sizeof($messages)){
				$messages = array(
					'insert' => array(
						'success' => $sys_language->get('class_form', 'insert_success_message'),
						'error' => $sys_language->get('class_form', 'insert_error_message')
					),
					'edit' => array(
						'success' => $sys_language->get('class_form', 'edit_success_message'),
						'error' => $sys_language->get('class_form', 'edit_error_message')
					)
				);
			}
			
			//Callbacks
			$error_callback = !empty($callbacks['error']) ? $callbacks['error'].'($("#'.$this->name.'"), message)' : '';
			$success_callback = !empty($callbacks['success']) ? $callbacks['success'].'($("#'.$this->name.'"), message, form_mode, form_data_text, result.process_id)' : '';
			$default_callback = !empty($callbacks['default']) ? $callbacks['default'].'($("#'.$this->name.'"))' : '';
			
			//Script
			$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.scrollTo.min.js');
			
			echo '
				<script>
					//Envio do formulário por AJAX
					var submit_default_text = $("#'.$this->name.' button[type=\'submit\']").text();

					$("#'.$this->name.'").prepend("<div class=\'ajax-result\'></div>").submit(function(){
						var form_mode = "'.$this->mode.'";
						var form_data = new Object();
						var form_data_text = new Object();
						var current_value;
						var current_text;
						var field_counter = new Object();

						form_data.fields = new Object();

						$("#'.$this->name.' input, #'.$this->name.' select, #'.$this->name.' textarea").not(":disabled").each(function(){
							if(($(this).attr("type") == "hidden") && $(this).hasClass("hidden-checkbox"))
								return true;

							var name = $(this).attr("name");
							var type = $(this).attr("type");

							if($(this).is("textarea") && $(this).hasClass("ckeditor"))
								type = "editor";

							if($(this).is("select"))
								type = "select";

							switch(type){
								case "checkbox":
									current_value = $(this).is(":checked") ? $(this).val() : "";
									break;

								case "radio":
									current_value = $("#'.$this->name.' input[name=\'" + name + "\']:checked").val();
									break;

								case "editor":
									current_value = CKEDITOR.instances[name].getData();
									break;

								case "select":
									current_value = $(this).val();
									current_text = $(this).find("option:selected").text();

									break;

								default:
									current_value = $(this).val();
									current_text = $(this).val();

									break;
							}

							if(typeof(name) != "undefined"){
								if(name.indexOf("[]") > 0){
									var new_name = name.replace("[]", "");

									if(typeof(form_data.fields[new_name]) == "undefined"){
										form_data.fields[new_name] = new Array();
										field_counter[new_name] = 0;
									}

									if(typeof(form_data_text[new_name]) == "undefined")
										form_data_text[new_name] = new Array();

									form_data.fields[new_name][field_counter[new_name]] = current_value;
									form_data_text[new_name][field_counter[new_name]] = current_text;

									field_counter[new_name]++;
								}
								else{
									form_data.fields[name] = current_value;
									form_data_text[name] = current_text;
								}
							}
						});

						form_data.object = "'.addslashes(urlencode(serialize($this))).'";
						form_data.after_queries = "'.addslashes(urlencode(serialize($after_queries))).'";
						form_data.messages = "'.addslashes(urlencode(serialize($messages))).'";

						$.post("app/core/util/ajax/handler?page=form", form_data, function(response){
							var result = $.parseJSON(response);
							var message;

							if(response.error_message != null){
								message = response.error_message;
								'.$error_callback.'
							}

							if(response.success_message != null)
								message = response.success_message;

							if(response.valid){
								'.$this->name.'_changes_count = 0;

								if(form_mode == "insert"){
									$("#'.$this->name.'")[0].reset();
									$(".password-strength-container").hide();
								}

								'.$success_callback.'
							}

							$("#'.$this->name.' button[type=\'submit\']").removeAttr("disabled").html(submit_default_text);
							'.$default_callback.'
						}, "json");

						return false;
					});

					//Callbacks
					function ajax_form_error(form_obj, message){
						form_obj.find(".ajax-result").html(message.html);
					}

					function ajax_form_success(form_obj, message, mode, response, process_id){
						form_obj.find(".ajax-result").html(message.html);
					}

					function ajax_form_callback(form_obj){
						$("body").scrollTop(form_obj.find(".ajax-result").offset().top);
					}
				</script>
			';
		}
		
		/**
		 * Carrega os valores dos campos do registro na tabela associada do banco de dados.
		 * 
		 * @param boolean $redirect_on_error Define se a página deve ser redirecionada, limpando os parâmetros e exibindo uma mensagem em caso de erro.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		private function load($redirect_on_error = true){
			global $db, $sys_language;
			
			if(in_array($this->mode, array('edit', 'view', 'delete')) && $this->record_id){
				$db->query('SELECT * FROM '.$this->db_table.' WHERE id = '.(int)$this->record_id);
				$record_values = $db->result(0);
				
				if(!empty($record_values->id)){
					$this->loaded_values['id'] = $record_values->id;
					
					foreach($this->db_fields as $field_name => $field_attr){
						if(!$field_attr['save'])
							continue;
						
						if(!$field_attr['related']){
							$field_type = (!$field_attr['type'] && $field_attr['multilang']) ? 'serialize' : $field_attr['type'];
							$field_value = $this->prepare_value($field_name, $record_values->$field_name, $field_type, $field_attr['is_array'], 'load');
						}
						else{
							$record_id = !empty($this->db_relationships[$field_attr['table']]['foreign_key_source']) ? $this->related_process_ids[$this->db_relationships[$field_attr['table']]['foreign_key_source']][0] : $this->record_id;

							$db->query('SELECT `'.$field_name.'`, id FROM '.$field_attr['table'].' WHERE `'.$this->db_relationships[$field_attr['table']]['foreign_key'].'` = '.$record_id, 'array');
							$result = $db->result();
							
							$field_type = (!$field_attr['type'] && $field_attr['multilang']) ? 'serialize' : $field_attr['type'];
							$field_value = $this->prepare_value($field_name, $field_attr['is_array'] ? $result : $result[0][$field_name], $field_type, $field_attr['is_array'], 'load');

							if(!sizeof($this->db_relationships[$field_attr['table']]['primary_keys'])){
								$this->db_relationships[$field_attr['table']]['primary_keys'] = array();

								foreach($result as $record)
									$this->db_relationships[$field_attr['table']]['primary_keys'][] = $record['id'];
							}
						}

						$this->loaded_values[$field_name] = $field_value;
					}
					
					return true;
				}
				elseif($redirect_on_error){
					\UI\Message::error($sys_language->get('class_form', 'invalid_record_message'));
					\URL\URL::redirect(\URL\URL::remove_params(URL, array('mode', 'id')));
				}
			}
			
			return false;
		}
		
		/**
		 * Descarrega os valores dos campos do registro na tabela associada do banco de dados.
		 */
		public function unload(){
			$this->loaded_values = array();
		}
		
		/**
		 * Define valores pré-definidos para os campos do formulário.
		 * 
		 * @param array $values Vetor onde a chave é o nome do campo e o valor é o valor do campo.
		 */
		public function set_values($values = array()){
			foreach($values as $field_name => $field_value){
				$this->loaded_values[$field_name] = $field_value;
				
				if(array_key_exists($field_name, $this->fields)){
					if($this->db_fields[$field_name]['is_array'] && is_array($field_value)){
						$i = 0;
						
						foreach($this->fields[$field_name] as $field)
							$field->set_value($field_value[$i++]);
					}
					else{
						$this->fields[$field_name][0]->set_value($field_value);
					}
				}
			}
		}
		
		/**
		 * Apaga o registro na tabela associada do banco de dados.
		 * 
		 * @param boolean $refresh Define se a página deve ser recarregada após a operação.
		 * @param array $ignore Vetor com os índices 'id', que indica um vetor de IDs de registros que devem ser ignorados na remoção; e 'message', que indica a mensagem de erro a ser exibida ao tentar apagar um registro ignorado.
		 * @param boolean $force Indica se deve forçar a execução das consultas SQL anteriores.
		 * @param array $before_queries Vetor com consultas SQL a serem realizadas antes de apagar o registro.
		 * @param array $after_queries Vetor com consultas SQL a serem realizadas após apagar o registro.
		 * @param boolean $show_message Define se a mensagem de sucesso/erro será exibida após o registro ser apagado.
		 * @param array $messages Vetor com os índices 'success', que indica a mensagem de sucesso ao apagar o registro; e 'error', que indica a mensagem de erro ao apagar o registro.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function delete($refresh = true, $ignore = array(), $force = false, $before_queries = array(), $after_queries = array(), $show_message = true, $messages = array()){
			global $db;
			global $sys_control;
			global $sys_user;
			global $sys_language;
			
			if($this->mode == 'delete'){
				//Mensagens
				if($show_message && !sizeof($messages)){
					$messages = array(
						'success' => $sys_language->get('class_form', 'delete_success_message'),
						'error' => $sys_language->get('class_form', 'delete_error_message')
					);
				}
				
				//Verifica a permissão de acesso
				$package = $sys_control->get_page_attr($sys_control->get_url(), 'package');
				$module = $sys_control->get_page_attr($sys_control->get_url(), 'module');
				
				$module_list_url = '/admin/'.$package.'/'.$module.'/list';
				
				if(\HTTP\Request::get('force'))
					$force = true;
				
				$permission = $sys_user->get_permission($package, $module, $this->mode);
				
				if($permission['granted']){
					$db->query('SELECT COUNT(*) AS total FROM '.$this->db_table.' WHERE id = '.$this->record_id);
					
					if($db->result(0)->total){
						$db->init_transaction();
						
						//Tentativa de remoção de registro não permitido
						if(in_array($this->record_id, (array)$ignore['id'])){
							$error = !empty($ignore['message']) ? $ignore['message'] : $sys_language->get('class_form', 'record_remove_error');
							
							\UI\Message::error($error);
							\URL\URL::redirect($module_list_url);
						}
						
						//Pega as consultas SQL a serem realizadas antes da remoção diretamente da classe DAO
						if(!sizeof($before_queries) && !empty($this->dao_class)){
							$dao_class = $this->dao_class;
							$before_queries = $dao_class::get_before_delete_queries($this->record_id);
						}
						
						//Executa as consultas SQL
						$query_result = false;
						$before_queries_result = (sizeof($before_queries) && $force) ? $db->multiple_query($before_queries) : true;
						
						if(empty($before_queries_result['error'])){
							try{
								$query_result = $db->query('DELETE FROM '.$this->db_table.' WHERE id = '.$this->record_id);
								
								//Pega as consultas SQL a serem realizadas depois da remoção diretamente da classe DAO
								if(!sizeof($after_queries) && !empty($this->dao_class)){
									$dao_class = $this->dao_class;
									$after_queries = $dao_class::get_after_delete_queries($this->record_id);
								}

								if(sizeof($after_queries))
									$db->multiple_query($after_queries);
							}
							catch(exception $e){
								$exception_message = $e->getMessage();
							}
						}
						else{
							$exception_message = $before_queries_result['error'];
						}
						
						$transaction_result = $db->end_transaction();
						
						if(empty($exception_message))
							$exception_message = $transaction_result['error'];
						
						if($show_message){
							if(empty($exception_message)){
								//Apaga possíveis arquivos relacionados ao registro
								foreach($this->fields as $field_array){
									foreach($field_array as $field){
										switch(get_class($field)){
											case 'Form\Image':
												\Storage\File::delete($field->get('folder'), $field->get('value'));
												break;

											case 'Form\Upload':
											case 'Form\Gallery':
												foreach($field->get('value') as $file)
													\Storage\File::delete($field->get('folder'), $file);

												break;
										}
									}
								}
								
								\UI\Message::success($messages['success']);
							}
							else{
								if(strpos($exception_message, 'foreign key constraint fails'))
									$complement = !$force ? '<strong>'.$sys_language->get('common', 'error').':</strong> '.$sys_language->get('class_form', 'foreign_key_warning').' (<a href="'.URL.'&force=1" onclick="return confirm(\''.$sys_language->get('class_form', 'force_record_remove_message').'\')">'.$sys_language->get('class_form', 'force_record_remove').'</a>)' : '<strong>'.$sys_language->get('common', 'error').':</strong> '.$sys_language->get('class_form', 'foreign_key_error');
								else
									$complement = $exception_message;
								
								if(!empty($complement))
									$complement = '<div class="detail"><div class="inner">'.$complement.'</div></div>';
								
								\UI\Message::error($messages['error'].$complement);
							}
						}
						
						if($refresh)
							\URL\URL::redirect($module_list_url);
						else
							return $query_result;
					}
					else{
						//Registro inválido
						\UI\Message::error($sys_language->get('class_form', 'invalid_record_delete_message'));
						\URL\URL::redirect($module_list_url);
					}
				}
			}
			
			return false;
		}
		
		/**
		 * Monta HTML com o resumo do formulário preenchido (para enviar por e-mail, gerar relatórios, etc).
		 * 
		 * @param array $fields Vetor multidimensional dos campos desejados, onde o índice é o nome do agrupamento e o valor é um vetor com os nomes dos campos (vazio para todos). 
		 * @return string HTML gerado.
		 */
		public function get_excerpt($fields = array()){
			global $sys_language;
			
			$html = '';
			$selected_fields = array();
			
			//Monta o vetor de campos
			if(sizeof($fields)){
				foreach($fields as $group => $field_array){
					foreach($field_array as $field_name)
						$selected_fields[$group][$field_name] = $this->fields[$field_name];
				}
			}
			else{
				$selected_fields = array('' => $this->fields);
			}
			
			//Exibe o resumo
			foreach($selected_fields as $group => $field_array){
				if(!empty($group))
					$html .= '<h2>'.$group.'</h2>';
				
				foreach($field_array as $field){
					$value = '';
					
					if(sizeof($field) > 1){
						/* TODO */
					}
					else{
						$field = reset($field);
						$field_value = $field->get('value');
						
						switch(get_class($field)){
							case 'Form\Textarea':
								$value = nl2br($field_value);
								break;
							
							case 'Form\Select':
							case 'Form\RadioGroup':
								$options = $field->get('options');
								$value = $field_value ? $options[$field_value] : '';
								
								break;
							
							case 'Form\Checkbox':
								$value = $field->get('checked') ? $sys_language->get('common', '_yes') : $sys_language->get('common', '_no');
								break;
							
							case 'Form\Money':
								$unit = $field->get('unit');
								$value = $field_value ? $unit['acronym'].' '.$field_value : '';
								
								break;
							
							case 'Form\CheckboxGroup':
								$options = $field->get('options');
								$checked_options = array();
								
								foreach($field_value as $checked_value)
									$checked_options[] = $options[$checked_value];
								
								$value = \Util\ArrayUtil::count_items($checked_options);
								break;
							
							case 'Form\TextGroup':
								$value = \Util\ArrayUtil::listify($field_value);
								break;
							
							case 'Form\TextInput':
								if($field_value){
									switch($field->get('type')){
										case 'time':
											$value = $field_value.'h';
											break;

										case 'url':
											$value = '<a href="'.$field_value.'" target="_blank">'.$field_value.'</a>';
											break;

										case 'email':
											$value = '<a href="mailto:'.$field_value.'">'.$field_value.'</a>';
											break;
										
										default:
											$value = $field_value;
									}
								}
								
								break;
							
							default:
								$value = $field_value;
						}
						
						if(empty($value))
							$value = '---';
						
						$html .= '
							<strong>'.$field->get('label').':</strong>
							<p>'.$value.'</p>
						';
					}
				}
			}
			
			return $html;
		}
		
		/**
		 * Retorna o ID do registro processado.
		 * 
		 * @return int|boolean ID do registro processado em caso de sucesso ou FALSE em caso de falha.
		 */
		public function get_process_id(){
			if($this->is_success())
				return $this->process_id;
			
			return false;
		}
		
		/**
		 * Retorna o ID do registro atual do formulário.
		 * 
		 * @return int|boolean ID do registro atual.
		 */
		public function get_record_id(){
			return $this->record_id;
		}
		
		/**
		 * Insere a URL encurtada do registro.
		 * 
		 * @param string $short_field Nome do campo da tabela do banco de dados onde deve ser gravada a URL encurtada.
		 * @param string $slug_field Nome do campo da tabela do banco de dados que contém o slug do registro.
		 * @param string $url_path Caminho da URL até o slug do registro, sem contar a base.
		 * @return boolean TRUE em caso de sucesso e FALSE em caso de falha.
		 */
		public function insert_short_url($short_field, $slug_field, $url_path){
			global $db;
			
			//Carrega as configurações do servidor
			$conf_server = new \System\Config('server');
			
			if($this->get_process_id()){
				$db->query('SELECT '.$slug_field.' FROM '.$this->db_table.' WHERE id = '.$this->get_process_id());
				$slug = $db->result(0)->$slug_field;
				
				\Storage\Folder::fix_path($url_path);
				$url = rtrim($conf_server->get('url_web'), '/').$url_path.$slug;
				$short_url = \URL\URL::shorten($url);
				
				if(!empty($short_url))
					return $db->query('UPDATE '.$this->db_table.' SET '.$short_field.' = "'.\URL\URL::shorten($url).'" WHERE id = '.$this->get_process_id());
				
				return false;
			}
		}
		
		/**
		 * Define se o formulário deve detectar alterações não salvas ao sair da página.
		 */
		public function detect_changes(){
			global $sys_assets, $sys_language;
			
			if($this->mode != 'view'){
				$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.livequery.min.js');
				
				$this->script .= '
					//Detecta alterações no formulário
					var '.$this->name.'_changes = [];
					var '.$this->name.'_changes_count = 0;
					var field_counter = 0;
					
					function get_current_value(element){
						var type = element.attr("type");
						var current_value = "";
						
						if(element.is("textarea") && element.hasClass("ckeditor"))
							type = "editor";
						
						switch(type){
							case "checkbox":
								current_value = element.is(":checked");
								break;

							case "radio":
								current_value = $("#'.$this->name.' input[name=\'" + element.attr("name") + "\']:checked").val();
								break;
							
							case "editor":
								current_value = CKEDITOR.instances[element.attr("name")].getData();
								break;

							default:
								current_value = element.val();
								break;
						}
						
						return current_value;
					}
					
					function is_array_field(field_name){
						if(typeof field_name != "undefined")
							return (field_name.substr((field_name.length - 2), 2) == "[]");
					}
					
					function get_array_field_name(field_name, count){
						if(is_array_field(field_name))
							field_name = field_name.substring(0, (field_name.length - 2)) + "[" + count + "]";
						
						return field_name;
					}
					
					function change_field_value(element){
						var field_name = is_array_field(element.attr("name")) ? element.data("array_field_name") : element.attr("name");
						
						if(!'.$this->name.'_changes[field_name].changed && get_current_value(element) != '.$this->name.'_changes[field_name].default_value){
							'.$this->name.'_changes[field_name].changed = true;
							'.$this->name.'_changes_count++;
						}
						else if(get_current_value(element) == '.$this->name.'_changes[field_name].default_value){
							'.$this->name.'_changes[field_name].changed = false;
							
							if('.$this->name.'_changes_count > 0)
								'.$this->name.'_changes_count--;
						}
					}
					
					$("#'.$this->name.' input, #'.$this->name.' select, #'.$this->name.' textarea").livequery(function(){
						var field_name = $(this).attr("name");
						
						if(is_array_field(field_name)){
							field_name = get_array_field_name(field_name, field_counter);
							field_counter++;
							
							$(this).data("array_field_name", field_name);
						}
						
						if(!(field_name in '.$this->name.'_changes))
							'.$this->name.'_changes[field_name] = {changed: false, default_value: get_current_value($(this))};
						
						if($(this).is("textarea") && $(this).hasClass("ckeditor")){
							editor = $(this);
							
							CKEDITOR.instances[$(this).attr("name")].on("change", function(e){
								change_field_value(editor);
							});
						}
						
						$(this).change(function(){
							change_field_value($(this));
						});
					});
					
					$(window).on("beforeunload", function(){
						if(!'.$this->name.'_submitted && '.$this->name.'_changes_count > 0)
							return "'.$sys_language->get('class_form', 'form_changes_warning').'";
					});
				';
			}
		}
		
		/**
		 * Exibe o formulário gerado.
		 * 
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @param boolean $highlight_required_fields Define se um asterisco ('*') deve ser exibido nos rótulos dos campos que não podem ficar em branco.
		 * @param string $submit_loading_text Texto a ser exibido no botão enquanto carrega o envio do formulário.
		 * @param boolean $force Indica se o formulário deve ser exibido independente da permissão de acesso do usuário.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public function display($echo = true, $highlight_required_fields = true, $submit_loading_text = '', $force = false){
			global $sys_control, $sys_user, $sys_language;
			
			if(empty($submit_loading_text))
				$submit_loading_text = $sys_language->get('common', 'loading').'...';
			
			//Scripts do formulário
			$this->script .= '
				//Foco nos campos
				$("#'.$this->name.' .label").not(".no-focus").click(function(){
					if($(this).find("input").length > 0)
						$(this).find("input:first:not(:focus)").focus();
					else if($(this).find("textarea").length > 0)
						$(this).find("textarea:first:not(:focus)").focus();
					else if($(this).find("select").length > 0)
						$(this).find("select:first:not(:focus)").focus();
				});
				
				//Esconde observações dos campos no modo de visualização
				$(".view-content").parent().find(".form-obs").remove();
				
				//Desabilita botão ao enviar o formulário
				var '.$this->name.'_submitted = false;
				
				$("#'.$this->name.'").submit(function(){
					'.$this->name.'_submitted = true;
					$("#'.$this->name.' button[type=\'submit\']").attr("disabled", true).text("'.$submit_loading_text.'");
				});
			';
			
			//Abas
			$tabs_html = '';
			
			if(sizeof($this->tabs)){
				$tabs_html = '<nav class="tabs-container">';
				
				foreach($this->tabs as $tab_id => $tab_label)
					$tabs_html .= '<a href="#" class="tab '.$tab_id.'" data-id="'.$tab_id.'">'.$tab_label.'</a>';
				
				$tabs_html .= '</nav>';
				
				$this->script .= '
					var hash = window.location.hash.replace("#", "");
					
					$("#'.$this->name.' .tabs-container .tab").click(function(){
						$(this).siblings(".tab").removeClass("current");
						$("#'.$this->name.' .tab-content").removeClass("current");
						$(this).addClass("current");
						$("#'.$this->name.' .tab-content[data-id=\'" + $(this).data("id") + "\']").addClass("current");
						
						window.location.hash = $(this).data("id");
						return false;
					});
					
					if(hash)
						$("#'.$this->name.' .tabs-container .tab[data-id=\'" + hash + "\']").click();
					else
						$("#'.$this->name.' .tabs-container .tab:first").click();
				';
			}
			
			//Marcação de campos obrigatórios
			$required_message_html = '';
			
			if($highlight_required_fields && ($this->mode != 'view') && sizeof($this->required_fields)){
				$js_fields = array();
				
				foreach($this->required_fields as $required_field)
					$js_fields[] = '"'.$required_field.'"';
				
				$this->script .= '
					//Marca campos obrigatórios
					var fields = new Array('.implode(', ', $js_fields).');
					
					for(var i = 0; i < fields.length; i++){
						if($("#'.$this->name.' .fieldgroup:not(.mixedgroup)").has(".label[rel=\'" + fields[i] + "\']").length > 0){
							if(!$("#'.$this->name.' .label[rel=\'" + fields[i] + "\']").parents(".fieldgroup").find("legend span.required").length)
								$("#'.$this->name.' .label[rel=\'" + fields[i] + "\']").parents(".fieldgroup").find("legend").prepend("<span class=\'required\' title=\''.$sys_language->get('class_form', 'required_field').'\'>*</span> ");
						}
						else{
							$("#'.$this->name.' .label[rel=\'" + fields[i] + "\']").not(".no-asterisk").find(".label-title:first").prepend("<span class=\'required\' title=\''.$sys_language->get('class_form', 'required_field').'\'>*</span> ");
						}
					}
				';
				
				$required_message_html = '<p class="required-message">'.sprintf($sys_language->get('class_form', 'required_fields_warning'), '<span class="required">*</span>').'</p>';
			}
			
			//Fecha o formulário
			$this->html = '
				<form name="'.$this->name.'" id="'.$this->name.'" method="'.$this->method.'" enctype="multipart/form-data" '.\UI\HTML::prepare_attr($this->attributes).'>
					'.$this->mode_bar_html.'
					'.$required_message_html.'
					'.$tabs_html.'
					'.$this->html.'
					<input type="hidden" name="'.$this->submit_name.'" value="1" />
				</form>
			';
			
			$return = $this->html;
			
			//Aplica a permissão de acesso
			if(!$force){
				$package = $sys_control->get_page_attr($sys_control->get_url(), 'package');
				$module = $sys_control->get_page_attr($sys_control->get_url(), 'module');
				
				$permission = $sys_user->get_permission($package, $module, $this->mode);
				
				if(!$permission['granted'])
					$return = \System\System::permission_error_message($permission['message']);
			}
			
			$return .= '
				<script>
					//Scripts do formulário
					$(document).ready(function(){
						'.\Formatter\String::compress($this->script, 'simple').'
					});
				</script>
			';
			
			if($echo)
				echo $return;
			else
				return $return;
		}
		
		/*-- Validação --*/
		
		/**
		 * Valida os campos do formulário.
		 * 
		 * @param boolean $echo $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @param string $message Mensagem a ser exibida acima da lista de erros.
		 * @param string $class Classes CSS a serem atribuídas à caixa que contém a lista de erros.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public function validate($echo = true, $message = '', $class = 'form-box error'){
			global $sys_language, $sys_assets;
			$html = '';
			
			//Campos obrigatórios
			foreach($this->db_fields as $field_id => $field_attr){
				if(array_key_exists('validation', $field_attr) && in_array('is_empty', $field_attr['validation']))
					$this->required_fields[] = $field_id;
			}
			
			//Validação dos campos
			if($this->is_submitted()){
				$error_list = array();
				
				foreach($this->db_fields as $field_id => $field_attr){
					if(array_key_exists($field_id, $this->fields) && array_key_exists('validation', $field_attr)){
						//Ignora campos desabilitados
						if(!$this->get($field_id, '', null, true) && in_array('ignore_disabled', $field_attr['validation']))
							continue;
						
						foreach($field_attr['validation'] as $validation_function => $validation_params){
							$field_array = $this->fields[$field_id];
							
							//Padroniza o vetor de validação
							if(is_int($validation_function)){
								$validation_function = $validation_params;
								$validation_params = array();
							}
							
							if($validation_function == 'ignore_disabled')
								continue;
							
							foreach($field_array as $field){
								//Valida o campo
								$field_validation = $field->validate($validation_function, $validation_params);

								//Retorno da validação
								if(!$field_validation['valid']){
									$this->valid = false;
									$validation_messages = !is_array($field_validation['message']) ? array($field_validation['message']) : $field_validation['message'];

									foreach($validation_messages as $validation_message)
										$error_list[$field_id][] = $validation_message;
									
									break;
								}
							}
						}
					}
				}
				
				//Exibe a lista de erros
				if(sizeof($error_list)){
					$html = '<div class="'.$class.'">';
					
					if(empty($message))
						$message = $sys_language->get('class_form', 'validation_message');
					
					if(!empty($message))
						$html .= '<p class="message">'.$message.'</p>';

					$html .= '<ul id="form-error-list">';

					foreach($error_list as $error_index => $error_array){
						$num_suberrors = sizeof($error_list[$error_index]);
						$i = 1;

						foreach($error_array as $error){
							if($i === 2)
								$html .= '<ul>';

							if(($num_suberrors > 1) && ($i === 1))
								$html .= '<li>'.$error;
							else
								$html .= '<li>'.$error.'</li>';

							if(($num_suberrors > 1) && ($i === $num_suberrors))
								$html .= '</ul></li>';

							$i++;
						}
					}

					$html .= '</ul>';

					//Script
					$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.scrollTo.min.js');

					$script = '
						$("#form-error-list li strong").each(function(){
							$(this).addClass("field").click(function(){
								var self = $(this);
								
								$.scrollTo(".label[rel=\'" + $(this).attr("rel") + "\']:first", {
									duration: 500,
									onAfter: function(){
										$("#'.$this->name.'").find("*[name=\'" + self.attr("rel") + "\']").focus();
									}
								});
							});
						});
					';

					if($echo)
						$this->script .= $script;
					else
						$html .= '<script>'.$script.'</script>';

					$html .= '</div>';
				}
			}
			
			if($echo)
				echo $html;
			else
				return $html;
		}
	}
?>