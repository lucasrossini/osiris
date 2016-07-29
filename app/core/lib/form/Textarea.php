<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo do tipo TEXTAREA.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 14/02/2014
	*/
	
	class Textarea extends Field{
		protected $maxlength;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param string $value Valor do campo.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param int $maxlength Limite de caracteres do campo (0 para ilimitado).
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array(), $maxlength = 0){
			parent::__construct($name, $label, $value, $attributes);
			
			$this->maxlength = (int)$maxlength;
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			global $sys_assets, $sys_language;
			
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					if($this->multilang){
						$hidden_inputs = '';
						
						foreach(\System\Language::get_available_languages() as $code => $language)
							$hidden_inputs .= '<input type="hidden" name="'.$this->id.'['.$code.']" value="'.$this->value[$code].'" />';
						
						$textarea_value = $this->value[\System\Language::get_current_lang()];
					}
					else{
						$hidden_inputs = '';
						$textarea_value = $this->value;
					}
					
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
							<span class="label-title">'.$this->label.$this->label_complement.'</span>
							<textarea name="'.$this->name.'" id="'.$this->id.'" '.\UI\HTML::prepare_attr($this->attributes).'>'.$textarea_value.'</textarea>
					';
					
					$this->script = '';
					
					//Limite de caracteres
					if(!$this->maxlength)
						$this->maxlength = $this->get_maxlength();
					
					if($this->maxlength){
						$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.limit.min.js');
						$chars = !$textarea_value ? $this->maxlength : ($this->maxlength - strlen($textarea_value));

						if($chars < 0)
							$chars = 0;

						$this->html .= '<span class="char-count"><span class="chars-left" id="'.$this->id.'_chars_left">'.$chars.'</span> '.$sys_language->get('class_form', 'chars_left').'</span>';
						$this->script .= '$("textarea[name=\''.$this->name.'\']").limit('.$this->maxlength.', "#'.$this->id.'_chars_left");';
					}
					
					$this->html .= '
							'.$this->get_tip().'
							'.$this->get_language_selector().'
							'.$hidden_inputs.'
						</div>
					';
					
					//Seletor de idioma
					if($this->multilang){
						$this->script .= '
							$("#'.$this->id.'-language-selector ul li").click(function(){
								var new_lang = $(this).data("lang");
								$("textarea[name=\''.$this->name.'\']").val($("input[name=\''.$this->id.'[" + new_lang + "]\']").val()).focus();
							});
							
							$("textarea[name=\''.$this->name.'\']").change(function(){
								var current_lang = $("#'.$this->id.'-language-selector ul li.current").data("lang");
								$("input[name=\''.$this->id.'[" + current_lang + "]\']").val($(this).val());
							});
						';
					}
					
					break;

				case 'view':
					$this->html = $this->view(nl2br($this->value));
					break;
			}
		}
	}
?>