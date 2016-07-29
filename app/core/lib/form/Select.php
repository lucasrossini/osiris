<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo do tipo SELECT.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 06/03/2014
	*/
	
	class Select extends Field{
		protected $options;
		
		/**
		 * Instancia um campo SELECT.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param mixed $value Valor do campo.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param array $options Vetor com os valores para montagem dos elementos OPTION, onde a chave é o nome do elemento e o valor é o valor do elemento.
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array(), $options = array()){
			parent::__construct($name, $label, $value, $attributes);

			$this->options = $options;
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					//HTML
					$options = '';
					
					if(sizeof($this->options)){
						foreach($this->options as $key => $value){
							$selected_attr = ((string)$this->value === (string)$key) ? 'selected' : '';
							$options .= '<option value="'.$key.'" '.$selected_attr.'>'.$value.'</option>';
						}
					}
					
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
							<span class="label-title">'.$this->label.$this->label_complement.'</span>
							<select data-value="'.$this->value.'" name="'.$this->name.'" id="'.$this->id.'" '.\UI\HTML::prepare_attr($this->attributes).'>'.$options.'</select>
							'.$this->get_tip().'
						</div>
					';
					
					break;
				
				case 'view':
					$selected_value = !empty($this->value) ? $this->options[$this->value] : '';
					
					$content = '
						<select data-value="'.$this->value.'" id="'.$this->name.'"><option value="'.$this->value.'" selected></option></select>
						'.$selected_value.'
					';
					
					$this->html = $this->view($content);
					break;
			}
			
			//Script
			$this->script = '
				$(document).ready(function(){
					$("select[name=\''.$this->name.'\']").trigger("change");
				});
			';
		}
		
		/**
		 * Carrega vetor de opções para um elemento SELECT.
		 * 
		 * @param string $db_table Tabela do banco de dados que contém os registros a serem utilizados como opções.
		 * @param string $option_format Padrão de exibição da opção, onde os campos da tabela do banco de dados devem estar entre colchetes para que seu valor seja substituído e a formatação a ser aplicada no valor do campo deve ser aplicada através de dois pontos (':') (ex.: $option_format = 'Texto [campo1:formatação] - ([campo2]) / ...').
		 * @param string $where_clause Cláusula condicional (WHERE) da consulta SQL a ser realizada.
		 * @param string $order_by Cláusula de ordenação (ORDER BY) da consulta SQL a ser realizada.
		 * @param string $option_value Nome do campo da tabela que será utilizado como valor do elemento OPTION.
		 * @param string $default_option Texto da opção padrão e sem valor a ser exibida pelo elemento (null para não exibí-lo). 
		 * @param string $empty_text Texto da opção sem valor exibida pelo elemento caso nenhum resultado seja retornado pela consulta SQL.
		 * @return array Vetor com as opções, onde a chave será o nome do elemento OPTION e o valor será o valor do elemento OPTION.
		 */
		public static function load_options($db_table, $option_format, $where_clause = 'TRUE', $order_by = '', $option_value = 'id', $default_option = '', $empty_text = ''){
			global $db;
			global $sys_language;
			
			//Opção padrão
			if(empty($default_option) && !is_null($default_option))
				$default_option = $sys_language->get('common', 'select');
			if(empty($empty_text))
				$empty_text = $sys_language->get('class_form', 'not_found');
			
			//Monta as opções
			$field_matches = \Util\Regex::extract_brackets($option_format);
			$db_fields = '';
			
			$field_types = array('' => '');
			
			foreach($field_matches as $field_match){
				$field_pieces = explode(':', $field_match);
				$field_name = $field_pieces[0];
				$field_type = $field_pieces[1];
				
				if($field_name != 'id'){
					$db_fields .= $field_name.', ';
					
					if(!empty($field_type)){
						$field_types[$field_name] = $field_type;
						$option_format = str_replace($field_match, $field_name, $option_format);
					}
				}
			}
			
			$db_fields = rtrim($db_fields, ', ');
			
			if(!empty($db_fields))
				$order_clause = !empty($order_by) ? $order_by : $db_fields;
			else
				$order_clause = 'id DESC';
			
			$db_fields = !empty($db_fields) ? ', '.$db_fields : '';
			
			$db->query('SELECT id'.$db_fields.' FROM '.$db_table.' WHERE '.$where_clause.' ORDER BY '.$order_clause);
			$records = $db->result();
			$options = !empty($default_option) ? array('' => $default_option) : array();
			
			if(sizeof($records)){
				foreach($records as $record){
					$option = $option_format;
					
					foreach($record as $field => $value)
						$option = str_replace('['.$field.']', self::format_option($record->$field, $field_types[$field]), $option);
					
					$options[$record->$option_value] = $option;
				}
			}
			else{
				$options = array('' => $empty_text);
			}
			
			return $options;
		}
		
		/**
		 * Formata uma opção para um elemento SELECT.
		 * 
		 * @param string $value Valor a ser formatado.
		 * @param string $type Tipo de formatação a ser realizada sobre o valor.
		 * @return string Valor formatado.
		 */
		private static function format_option($value, $type = ''){
			switch($type){
				case 'date': //Data
					$value = \DateTime\Date::convert($value);
					break;
				
				case 'time': //Hora
					$value = \DateTime\Time::sql2time($value);
					break;
				
				case 'float': //Valor decimal
					$value = \Formatter\Number::sql2number($value);
					break;
			}
			
			return $value;
		}
		
		/**
		 * Exibe um elemento SELECT que muda os parâmetros GET da página atual de acordo com a opção selecionada.
		 * 
		 * @param string $name Nome/ID do elemento SELECT.
		 * @param string $label Rótulo a ser exibido acima do elemento.
		 * @param array $options Vetor com os valores para montagem dos elementos OPTION, onde a chave será o nome do elemento e o valor será o valor do elemento.
		 * @param array $remove_params Parâmetros GET a serem removidos da URL atual.
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public static function display_page_changer($name, $label, $options = array(), $remove_params = array(), $echo = true){
			$options_html = '';
			
			foreach($options as $option_value => $option_text){
				$selected = ((string)\HTTP\Request::get($name) === (string)$option_value) ? 'selected' : '';
				$options_html .= '<option value="'.$option_value.'" '.$selected.'>'.$option_text.'</option>';
			}
			
			$html = '
				<div class="default-form page-changer">
					<label>
						<span class="label-title">'.$label.'</span>
						<select name="'.$name.'" id="'.$name.'" onchange="change_select(\''.$name.'=\' + this.value, \''.\URL\URL::remove_params(URL, array_merge(array($name), $remove_params)).'\')">
							'.$options_html.'
						</select>
					</label>
				</div>
			';
			
			if($echo)
				echo $html;
			else
				return $html;
		}
		
		/**
		 * Transforma um vetor em elementos OPTION de um SELECT.
		 * 
		 * @param array $array Vetor de opções, onde a chave é o valor da opção e o valor é o texto da opção.
		 * @param string $selected Valor da opção pré-selecionada.
		 * @return string HTML das opções.
		 */
		public static function array2options($array = array(), $selected = null){
			$options = '';
			
			foreach($array as $key => $value){
				$selected_attr = (!is_null($selected) && ((string)$selected === (string)$key)) ? 'selected' : '';
				$options .= '<option value="'.$key.'" '.$selected_attr.'>'.$value.'</option>';
			}
			
			return $options;
		}
		
		/*-- Validação --*/
		
		/**
		 * @see FieldValidator::is_empty()
		 */
		public function is_empty(){
			global $sys_language;
			$invalid = is_array($this->value) ? \Util\ArrayUtil::is_empty($this->value) : !$this->value;
			
			if($invalid)
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_empty_select'), '<strong rel="'.$this->id.'">'.$this->label.'</strong>'));
			
			return self::valid();
		}
	}
?>