<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo do tipo INPUT para preenchimento de data.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 27/01/2014
	*/
	
	class Date extends Field{
		protected $min;
		protected $max;
		protected $icon;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param string $value Valor do campo.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param string $min Data mínima possível para seleção (formato '00/00/0000').
		 * @param string $max Data máxima possível para seleção (formato '00/00/0000').
		 * @param string $icon Arquivo de imagem do ícone que representa o calendário.
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array(), $min = '', $max = '', $icon = 'app/assets/js/jquery/ui/datepicker/images/calendar.png'){
			parent::__construct($name, $label, $value, $attributes);

			$this->min = $min;
			$this->max = $max;
			$this->icon = $icon;
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			global $sys_assets, $sys_language;
			
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					//Recursos necessários
					$sys_assets->load('css', 'app/assets/js/jquery/ui/theme/css/jquery.ui.all.css');
					$sys_assets->load('css', 'app/assets/js/jquery/ui/datepicker/css/jquery.ui.datepicker.css');

					$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.livequery.min.js');
					$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.maskedinput.min.js');
					$sys_assets->load('js', 'app/assets/js/jquery/ui/jquery.ui.core.min.js');
					$sys_assets->load('js', 'app/assets/js/jquery/ui/jquery.ui.widget.min.js');
					$sys_assets->load('js', 'app/assets/js/jquery/ui/datepicker/jquery.ui.datepicker.min.js');

					if(\System\Language::get_current_lang() != 'en')
						$sys_assets->load('js', 'app/assets/js/jquery/ui/datepicker/lang/jquery.ui.datepicker-'.\System\Language::get_current_lang(true).'.min.js');
					
					//HTML
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label date-input-container">
							<span class="label-title">'.$this->label.$this->label_complement.'</span>
							<input type="text" name="'.$this->name.'" id="'.$this->id.'" value="'.$this->value.'" '.\UI\HTML::prepare_attr($this->attributes).' />
							'.$this->get_tip().'
						</div>
					';

					//Script
					$min_date = empty($this->min) ? 'null' : '"'.$this->min.'"';
					$max_date = empty($this->max) ? 'null' : '"'.$this->max.'"';

					$this->script = '
						//Aplica a máscara
						$("input[name=\''.$this->name.'\']").mask("'.$sys_language->get('class_form', 'date_mask').'");

						//Adiciona o calendário
						$("input[name=\''.$this->name.'\']").datepicker({
							showOn: "button",
							buttonImage: "'.$this->icon.'",
							buttonImageOnly: true,
							buttonText: "'.$sys_language->get('class_form', 'calendar').'",
							dateFormat: "'.$sys_language->get('class_form', 'date_format').'",
							minDate: '.$min_date.',
							maxDate: '.$max_date.'
						});
					';
					
					break;

				case 'view':
					$this->html = $this->view();
					break;
			}
		}
		
		/*-- Validação --*/
		
		/**
		 * @see FieldValidator::compare()
		 */
		public function compare($type, $with){
			global $sys_language;
			
			if((string)$this->value !== ''){
				if(is_object($with) && is_a($with, '\Form\Field')){
					$compared_with = 'field';
					$compared_value = $with->get('value');
					$error_related_to = '<strong rel="'.$with->get('id').'">'.$with->get('label').'</strong>';
				}
				else{
					$compared_with = 'value';
					$compared_value = $with;
					$error_related_to = '"'.$with.'"';
				}

				$message_type = '';

				switch($type){
					case 'equal': //Igual
						if((int)\DateTime\Date::interval($this->value, $compared_value) !== 0)
							$message_type = 'validation_e_'.$compared_with;

						break;

					case 'not_equal': //Diferente
						if((int)\DateTime\Date::interval($this->value, $compared_value) === 0)
							$message_type = 'validation_ne_'.$compared_with;

						break;

					case 'greater': //Maior ou igual
						if((int)\DateTime\Date::interval($this->value, $compared_value) > 0)
							$message_type = 'validation_gt_'.$compared_with;

						break;

					case 'lower': //Menor ou igual
						if((int)\DateTime\Date::interval($compared_value, $this->value) > 0)
							$message_type = 'validation_lt_'.$compared_with;

						break;
				}

				if(!empty($message_type))
					return self::invalid(sprintf($sys_language->get('class_form', $message_type), '<strong rel="'.$this->id.'">'.$this->label.'</strong>', $error_related_to));
			}
			
			return self::valid();
		}
	}
?>