<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo de texto do tipo INPUT.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 26/03/2014
	*/
	
	class TextInput extends Field{
		protected $type;
		protected $maxlength;
		protected $mask;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param string $value Valor do campo.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param string $type Tipo do campo.
		 * @param int $maxlength Limite de caracteres do campo (0 para ilimitado).
		 * @param string $mask Máscara do campo.
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array(), $type = 'text', $maxlength = 0, $mask = ''){
			parent::__construct($name, $label, $value, $attributes);

			$this->type = $type;
			$this->maxlength = (int)$maxlength;
			
			//Tipos de campo
			switch($type){
				case 'time':
					$mask = '99:99';
					break;

				case 'phone':
					$mask = '(99)9999-9999';
					break;

				case 'cpf':
					$mask = '999.999.999-99';
					break;

				case 'cnpj':
					$mask = '99.999.999/9999-99';
					break;

				case 'cep':
					$mask = '99999-999';
					break;
			}
			
			$this->mask = $mask;
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			global $sys_assets;
			$this->html = $this->script = '';
			
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					//Limite de caracteres
					if(!$this->maxlength)
						$this->maxlength = $this->get_maxlength();
					
					if($this->maxlength)
						$this->attributes['maxlength'] = $this->maxlength;
					
					//Valor 'type' do campo
					switch($this->type){
						case 'email':
						case 'url':
						case 'time':
							$input_type = $this->type;
							break;

						case 'phone':
							$input_type = 'tel';
							break;

						default:
							$input_type = 'text';
					}
					
					if($this->multilang){
						$hidden_inputs = '';
						
						foreach(\System\Language::get_available_languages() as $code => $language)
							$hidden_inputs .= '<input type="hidden" name="'.$this->id.'['.$code.']" value="'.$this->value[$code].'" />';
						
						$input_value = $this->value[\System\Language::get_current_lang()];
					}
					else{
						$hidden_inputs = '';
						$input_value = $this->value;
					}
					
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
							<span class="label-title">'.$this->label.$this->label_complement.'</span>
							<input type="'.$input_type.'" name="'.$this->name.'" id="'.$this->id.'" value="'.$input_value.'" '.\UI\HTML::prepare_attr($this->attributes).' />
							'.$this->get_tip().'
							'.$this->get_language_selector().'
							'.$hidden_inputs.'
						</div>
					';
					
					$this->script = '';

					//Máscara do campo
					if($this->mask){
						$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.maskedinput.min.js');

						$this->script .= '
							$(document).ready(function(){
								$("input[name=\''.$this->name.'\']").mask("'.$this->mask.'");
							});
						';
					}
					
					//Campo de URL
					if($this->type == 'url'){
						$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.cursor.js');
						
						$this->script = '
							//Campo de URL
							$("input[name=\''.$this->name.'\']").focus(function(){
								if($(this).val() == ""){
									$(this).val("http://").focusEnd();
								}
							});

							$("input[name=\''.$this->name.'\']").blur(function(){
								if($(this).val() == "http://")
									$(this).val("");
							});
						';
					}
					
					//Seletor de idioma
					if($this->multilang){
						$this->script .= '
							$("#'.$this->id.'-language-selector ul li").click(function(){
								var new_lang = $(this).data("lang");
								$("input[name=\''.$this->name.'\']").val($("input[name=\''.$this->id.'[" + new_lang + "]\']").val());
							});
							
							$("input[name=\''.$this->name.'\']").change(function(){
								var current_lang = $("#'.$this->id.'-language-selector ul li.current").data("lang");
								$("input[name=\''.$this->id.'[" + current_lang + "]\']").val($(this).val());
							});
						';
					}

					break;

				case 'view':
					$content = $this->value;

					if(!empty($content)){
						switch($this->type){
							case 'time':
								$content = $content.'h';
								break;

							case 'url':
								$content = '<a href="'.$content.'" target="_blank">'.$content.'</a>';
								break;

							case 'email':
								$content = '<a href="mailto:'.$content.'">'.$content.'</a>';
								break;
						}
					}

					$this->html = $this->view($content);
					break;
			}
		}
	}
?>