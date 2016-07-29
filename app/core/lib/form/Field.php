<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo do formulário.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 23/04/2014
	*/
	
	abstract class Field extends FieldValidator{
		protected $id;
		protected $name;
		protected $label;
		protected $value;
		protected $attributes;
		protected $tip;
		protected $label_complement;
		
		protected $html = '';
		protected $script = '';
		
		protected $form;
		protected $multilang;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param mixed $value Valor do campo.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array()){
			$this->id = str_replace('[]', '', $name);
			$this->name = $name;
			$this->label = $label;
			$this->value = $value;
			$this->attributes = $attributes;
		}
		
		/**
		 * Monta o HTML + scripts do campo.
		 */
		public abstract function render();
		
		/**
		 * Retorna o valor de um determinado atributo do objeto.
		 * 
		 * @param string $attr Nome do atributo a ser retornado.
		 * @param mixed $index Índice desejado do vetor retornado caso o valor do atributo seja um vetor.
		 * @return mixed Valor do atributo.
		 */
		public function get($attr, $index = null){
			if(array_key_exists($attr, get_class_vars(get_called_class())))
				return (is_array($this->$attr) && !is_null($index)) ? $this->$attr[$index] : $this->$attr;
			
			return null;
		}
		
		/**
		 * Define o valor de um determinado atributo do objeto.
		 * 
		 * @param string $attr Nome do atributo a ser definido.
		 * @param mixed $value Valor a ser gravado no atributo.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function set($attr, $value){
			if(array_key_exists($attr, get_class_vars(get_called_class()))){
				$this->$attr = $value;
				return true;
			}
			
			return false;
		}
		
		/**
		 * Define o valor do campo.
		 * 
		 * @param mixed $value Valor do campo.
		 * @param int $index Índice do campo caso ele seja um vetor.
		 */
		public function set_value($value, $index = null){
			if($this->form->get_field_attr($this->id, 'is_array') && is_array($value) && !is_null($index))
				$value = $value[$index];
			
			$this->value = $value;
		}

		/**
		 * Exibe o valor de um campo em modo de visualização.
		 * 
		 * @param string $content Conteúdo a ser exibido.
		 * @return string HTML do conteúdo.
		 */
		protected function view($content = ''){
			global $sys_language;
			$empty_text = $sys_language->get('class_form', 'nothing_to_show');
			
			if(empty($content))
				$content = $this->value;
			
			//Múltiplos idiomas
			if(is_array($content)){
				$aux_content = '';
				
				foreach($content as $language_code => $value)
					$aux_content .= '<div class="lang" style="background-image: url(\'app/lang/flags/'.$language_code.'.png\')">'.$value.'</div>';
				
				$content = $aux_content;
			}
			
			if(empty($content))
				$content = '<span class="empty-content">'.$empty_text.'</span>';
			
			return '
				<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
					<span class="label-title">'.$this->label.'</span>
					<div class="view-content">'.$content.'</div>
				</div>
			';
		}
		
		/**
		 * Carrega o texto informativo do campo.
		 * 
		 * @return string Texto informativo.
		 */
		protected function get_tip(){
			return !empty($this->tip) ? '<p class="field-tip">'.$this->tip.'</p>' : '';
		}
		
		/**
		 * Carrega o HTML do seletor de idioma do campo.
		 * 
		 * @return HTML montado.
		 */
		protected function get_language_selector(){
			$html = '';
			
			if($this->multilang){
				$current_language = \System\Language::get_current_lang();
				$languages = \System\Language::get_available_languages();
				
				$html = '
					<div class="language-selector" id="'.$this->id.'-language-selector">
						<span class="current" style="background-image: url(\'app/lang/flags/'.$current_language.'.png\')" title="'.$languages[$current_language].'">'.$languages[$current_language].'</span>
						<ul>
				';
				
				foreach($languages as $code => $language){
					$current = ($code == $current_language) ? 'current' : '';
					$html .= '<li data-lang="'.$code.'" class="'.$current.'" style="background-image: url(\'app/lang/flags/'.$code.'.png\')">'.$language.'</li>';
				}
				
				$html .= '
						</ul>
					</div>
				';
			}
			
			return $html;
		}
		
		/**
		 * Carrega o limite de caracteres
		 * 
		 * @return int Limite de caracteres.
		 */
		protected function get_maxlength(){
			global $db;
			$table = $this->form->get_table();
			
			if($table)
				return $db->field_length($table, $this->id);
			
			return 0;
		}
		
		/**
		 * Valida o campo.
		 * 
		 * @param string $function Nome da função de validação.
		 * @param array $params Vetor de parâmetros, cujo índice é o nome do parâmetro e o valor é o valor do parâmetro.
		 * @return array Vetor de validação.
		 */
		public function validate($function, $params = array()){
			//Ordena os parâmetros da função de validação
			$ordered_params = $params;

			if(sizeof($params) > 1){
				$ordered_params = array();
				$reflection_method = new \ReflectionMethod(get_class(), $function);

				foreach($reflection_method->getParameters() as $reflection_param){
					$param_name = $reflection_param->getName();
					$param_value = array_key_exists($param_name, $params) ? $params[$param_name] : $reflection_param->getDefaultValue();

					if(is_array($param_value))
						$param_value = \Util\ArrayUtil::remove('', $param_value, true);

					$ordered_params[] = $param_value;
				}
			}
			
			//Chama e retorna o resultado da função
			return call_user_func_array(array($this, $function), $ordered_params);
		}
	}
?>