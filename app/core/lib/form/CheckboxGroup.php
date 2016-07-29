<?php
	namespace Form;
	
	/**
	 * Classe que representa um grupo de campos do tipo checkbox.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 12/02/2014
	*/
	
	class CheckboxGroup extends Field{
		protected $options;
		protected $check_all;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param array $value Valores dos campos marcados.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param array $options Vetor com os valores para montagem dos itens, onde a chave é o rótulo do item e o valor é o valor do item.
		 * @param boolean $check_all Define se as opções de "marcar/desmarcar tudo" devem ser exibidas.
		 */
		public function __construct($name, $label = '', $value = array(), $attributes = array(), $options = array(), $check_all = true){
			parent::__construct($name, $label, $value, $attributes);

			$this->options = $options;
			$this->check_all = $check_all;
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			global $sys_language;
			$options = '';
			
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					//HTML
					if(sizeof($this->options)){
						$attributes = \UI\HTML::prepare_attr($this->attributes);

						foreach($this->options as $key => $value){
							$checked_attr = (is_array($this->value) && in_array($key, $this->value)) ? 'checked' : '';

							$options .= '
								<label>
									<input type="checkbox" name="'.$this->name.'[]" value="'.$key.'" '.$checked_attr.' '.$attributes.' />
									'.$value.'
								</label>
							';
						}
					}
					
					$check_all_html = $this->check_all ? '<span class="checkgroup-options"><a href="#" class="icon check-all">'.$sys_language->get('class_form', 'check_all').'</a> / <a href="#" class="icon uncheck-all">'.$sys_language->get('class_form', 'uncheck_all').'</a></span>' : '';
					
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
							<fieldset class="checkgroup">
								<legend class="label-title">'.$this->label.$this->label_complement.$check_all_html.'</legend>
								'.$options.'
							</fieldset>
							
							'.$this->get_tip().'
						</div>
					';
					
					//Script
					if($this->check_all){
						$this->script = '
							//Marcar/desmarcar tudo
							$("#label-'.$this->id.' .check-all").click(function(){ $(this).parents(".checkgroup").find("input[type=\'checkbox\']").attr("checked", true).change(); return false; });
							$("#label-'.$this->id.' .uncheck-all").click(function(){ $(this).parents(".checkgroup").find("input[type=\'checkbox\']").removeAttr("checked").change(); return false; });
						';
					}
					
					break;
				
				case 'view':
					$content = '';
					
					if(sizeof($this->value)){
						$checked_options = array();

						foreach($this->value as $checked_value)
							$checked_options[] = $this->options[$checked_value];
						
						$content = \Util\ArrayUtil::count_items($checked_options);
					}
					
					$this->html = $this->view($content);
					break;
			}
		}
		
		/*-- Validação --*/
		
		/**
		 * @see FieldValidator::is_empty()
		 */
		public function is_empty(){
			global $sys_language;
			
			if(!sizeof($this->value))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_empty_select'), '<strong rel="'.$this->id.'">'.$this->id.'</strong>'));
			
			return self::valid();
		}
	}
?>