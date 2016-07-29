<?php
	namespace Form;
	
	/**
	 * Classe para validação de campos do formulário.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 23/04/2014
	*/
	
	abstract class FieldValidator{
		/**
		 * Retorna um vetor de validação positiva.
		 * 
		 * @return array Vetor de validação.
		 */
		protected static function valid(){
			return array('valid' => true, 'message' => '');
		}
		
		/**
		 * Retorna um vetor de validação negativa.
		 * 
		 * @param string $message Mensagem de validação.
		 * @return array Vetor de validação.
		 */
		protected static function invalid($message){
			return array('valid' => false, 'message' => $message);
		}
		
		/*-- Validações --*/
		
		/**
		 * Campo em branco.
		 * 
		 * @return array Vetor de validação.
		 */
		public function is_empty(){
			global $sys_language;
			
			$value = $this->get('value');
			$invalid = is_array($value) ? \Util\ArrayUtil::is_empty($value) : !strlen(trim($value));
			
			if($invalid)
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_empty'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * E-mail válido.
		 * 
		 * @return array Vetor de validação.
		 */
		public function is_email(){
			global $sys_language;
			
			if($this->get('value') && !Validator::is_email($this->get('value')))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_email'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * Opção válida.
		 * 
		 * @return array Vetor de validação.
		 */
		public function is_valid_option(){
			global $sys_language;
			
			if(!array_key_exists($this->get('value'), $this->get('options')))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_valid_option'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * Arquivos inválidos.
		 * 
		 * @return array Vetor de validação.
		 */
		public function is_file(){
			global $sys_language;
			$value = $this->get('value');
			
			if(!is_array($value))
				$value = array($value);
			
			foreach($value as $file){
				if(!empty($file) && !\Storage\File::exists($this->get('folder').$file))
					return self::invalid(sprintf($sys_language->get('class_form', 'validation_file'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			}
			
			return self::valid();
		}
		
		/**
		 * CPF válido.
		 * 
		 * @return array Vetor de validação.
		 */
		public function is_cpf(){
			global $sys_language;
			
			if($this->get('value') && !Validator::is_cpf($this->get('value')))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_cpf'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * CNPJ válido.
		 * 
		 * @return array Vetor de validação.
		 */
		public function is_cnpj(){
			global $sys_language;
			
			if($this->get('value') && !Validator::is_cnpj($this->get('value')))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_cnpj'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * Possui caracteres especiais (exceto '.' e '_').
		 * 
		 * @return array Vetor de validação.
		 */
		public function has_special_chars(){
			global $sys_language;
			
			if(Validator::has_special_chars($this->get('value')))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_special_chars'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * Possui tags HTML.
		 * 
		 * @return array Vetor de validação.
		 */
		public function has_html_tags(){
			global $sys_language;
			
			if($this->get('value') && ($this->get('value') != strip_tags($this->get('value'))))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_html_tags'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * Número inteiro válido.
		 * 
		 * @return array Vetor de validação.
		 */
		public function is_number(){
			global $sys_language;
			
			if($this->get('value') && !is_numeric($this->get('value')))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_integer'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * Número decimal válido.
		 * 
		 * @return array Vetor de validação.
		 */
		public function is_decimal(){
			global $sys_language;
			
			if($this->get('value') && !is_numeric(str_replace(',', '.', str_replace('.', '', $this->get('value')))))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_float'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * Data válida.
		 * 
		 * @return array Vetor de validação.
		 */
		public function is_date(){
			global $sys_language;
			
			if($this->get('value') && !Validator::is_date($this->get('value')))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_date'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * Hora válida.
		 * 
		 * @return array Vetor de validação.
		 */
		public function is_time(){
			global $sys_language;
			
			if($this->get('value') && !Validator::is_time($this->get('value')))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_time'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * Ano válido.
		 * 
		 * @return array Vetor de validação.
		 */
		public function is_year(){
			global $sys_language;
			
			if($this->get('value') && !Validator::is_year($this->get('value')))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_year'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * Captcha correto.
		 * 
		 * @return array Vetor de validação.
		 */
		public function correct_captcha(){
			global $sys_language;
			$securimage = new \Securimage();
			
			if(!$securimage->check($this->get('value')))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_captcha'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * Valor já existe na tabela.
		 * 
		 * @param string $table Tabela a ser verificada.
		 * @param string $field Nome do campo da tabela.
		 * @param array $ignore Vetor de IDs dos registros que devem ser ignorados na verificação.
		 * @return array Vetor de validação.
		 */
		public function already_exists($table = '', $field = '', $ignore = array()){
			global $db, $sys_language;
			
			//Utiliza a própria tabela se nenhuma for definida
			if(empty($table))
				$table = $this->get('form')->get_table();
			
			//Utiliza o próprio nome do campo se nenhum for definido
			if(empty($field))
				$field = $this->get('id');
			
			//Se a tabela a ser verificada é a mesma do formulário, o formulário estiver em modo de edição e nenhum ID de registro estiver sendo ignorado, utiliza o ID do registro atual
			if(($this->get('form')->get_mode() == 'edit') && ($table == $this->get('form')->get_table()) && !sizeof($ignore))
				$ignore = array($this->get('form')->get_record_id());
			
			$ignore_clause = sizeof($ignore) ? ' AND id NOT IN ('.implode(',', $ignore).')' : '';
			$db->query('SELECT COUNT(*) AS total FROM '.$table.' WHERE '.$field.' = "'.addslashes($this->get('value')).'"'.$ignore_clause);
			
			if($db->result(0)->total)
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_already'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * Quantidade de caracteres do campo.
		 * 
		 * @param int $min Quantidade mínima.
		 * @param int $max Quantidade máxima.
		 * @return array Vetor de validação.
		 */
		public function has_length($min, $max){
			global $sys_language;
			
			$value_length = strlen(trim($this->get('value')));
			$message = '';
			
			if(($value_length < $min) || ($value_length > $max)){
				if(($min > 0) && ($max > 0))
					$message = sprintf($sys_language->get('class_form', 'validation_length_between'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>', $min, $max);
				elseif($min > 0)
					$message = sprintf($sys_language->get('class_form', 'validation_length_min'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>', $min);
				elseif($max > 0)
					$message = sprintf($sys_language->get('class_form', 'validation_length_max'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>', $max);
			}
			
			if(!empty($message))
				return self::invalid($message);
			
			return self::valid();
		}
		
		/**
		 * Entre 2 valores numéricos.
		 * 
		 * @param int $min Valor mínimo.
		 * @param int $max Valor máximo.
		 * @return array Vetor de validação.
		 */
		public function is_between($min, $max){
			global $sys_language;
			
			if(!Validator::between($this->get('value'), $min, $max))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_value_between'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>', $min, $max));
			
			return self::valid();
		}
		
		/**
		 * URL válida.
		 * 
		 * @return array Vetor de validação.
		 */
		public function is_url(){
			global $sys_language;
			
			if($this->get('value') && !Validator::is_url($this->get('value')))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_url'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
			
			return self::valid();
		}
		
		/**
		 * Possui valores repetidos.
		 * 
		 * @return array Vetor de validação.
		 */
		public function has_repeated_entries(){
			global $sys_language;
			
			if(is_array($this->get('value')) && sizeof($this->get('value')) > 1){
				$count_array = array_count_values($this->get('value'));

				foreach($count_array as $value => $count){
					if(!empty($value) && ($count > 1))
						return self::invalid(sprintf($sys_language->get('class_form', 'validation_repeat'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
				}
			}
			
			return self::valid();
		}
		
		/**
		 * Quantidade de caracteres maiúsculos.
		 * 
		 * @return array Vetor de validação.
		 */
		public function check_uppercase($percentage){
			global $sys_language;
			
			if(Validator::check_uppercase_count($this->get('value')) >= $percentage)
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_uppercase'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>', $percentage));
			
			return self::valid();
		}
		
		/**
		 * Quantidade de caracteres minúsculos.
		 * 
		 * @return array Vetor de validação.
		 */
		public function check_lowercase($percentage){
			global $sys_language;
			
			if(Validator::check_lowercase_count($this->get('value')) >= $percentage)
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_lowercase'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>', $percentage, ucwords($this->get('value'))));
			
			return self::valid();
		}
		
		/**
		 * Compara valor.
		 * 
		 * @param string $type Tipo de comparação, que pode ser 'equal', 'not_equal', 'lower' ou 'greater'.
		 * @param mixed $with Valor a ser comparado.
		 * @return array Vetor de validação.
		 */
		public function compare($type, $with){
			global $sys_language;
			
			if((string)$this->get('value') !== ''){
				if(is_object($with) && is_a($with, '\Form\Field')){
					$compared_with = 'field';
					$compared_value = $with->get('value');
					$error_related_to = '<strong rel="'.$with->get('id').'">'.$with->get('label').'</strong>';
				}
				else{
					$compared_with = 'value';
					$compared_value = $with;
					$error_related_to = !is_numeric($with) ? '"'.$with.'"' : $with;
				}

				$message_type = '';

				switch($type){
					case 'equal': //Igual
						if((string)$this->get('value') !== (string)$compared_value)
							$message_type = 'validation_e_'.$compared_with;

						break;

					case 'not_equal': //Diferente
						if((string)$this->get('value') === (string)$compared_value)
							$message_type = 'validation_ne_'.$compared_with;

						break;

					case 'greater': //Maior ou igual
						if((float)\Formatter\Number::number2sql($this->get('value')) < (float)\Formatter\Number::number2sql($compared_value))
							$message_type = 'validation_gt_'.$compared_with;

						break;

					case 'lower': //Menor ou igual
						if((float)\Formatter\Number::number2sql($this->get('value')) > (float)\Formatter\Number::number2sql($compared_value))
							$message_type = 'validation_lt_'.$compared_with;

						break;
				}

				if(!empty($message_type))
					return self::invalid(sprintf($sys_language->get('class_form', $message_type), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>', $error_related_to));
			}
			
			return self::valid();
		}
		
		/**
		 * Necessita da validação de outros campos para seu preenchimento.
		 * 
		 * @param array $fields Vetor multidimensional com os índices 'object', que contém o objeto de campo do formulário; e 'validation', que contém o vetor com as validações necessárias para o campo.
		 * @return array Vetor de validação.
		 */
		public function requires($fields = array()){
			global $sys_language;
			$messages = array();
			
			foreach($fields as $field){
				foreach($field['validation'] as $validation_function => $validation_params){
					//Padroniza o vetor de validação
					if(is_int($validation_function)){
						$validation_function = $validation_params;
						$validation_params = array();
					}
					
					//Valida o campo
					$field_validation = $field['object']->validate($validation_function, $validation_params);
					
					if(!$field_validation['valid']){
						$messages[] = $field_validation['message'];
						break;
					}
				}
			}
			
			if(sizeof($messages)){
				array_unshift($messages, sprintf($sys_language->get('class_form', 'validation_requires'), '<strong rel="'.$this->get('id').'">'.$this->get('label').'</strong>'));
				return self::invalid($messages);
			}
			
			return self::valid();
		}
	}
?>