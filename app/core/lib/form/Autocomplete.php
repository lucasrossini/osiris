<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo do tipo INPUT com preenchimento automático.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 04/04/2014
	*/
	
	class Autocomplete extends Field{
		protected $source;
		protected $require;
		protected $multiple;
		protected $save_action;
		protected $min_length;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param mixed $value Valor do campo.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param mixed $source Vetor de opções (onde a chave é o valor e o valor é o rótulo) ou endereço da página que retorna as opções por AJAX no formato JSON.
		 * @param boolean $require Define se a seleção de uma opção existente é obrigatória.
		 * @param boolean $multiple Define se múltiplos itens podem ser selecionados.
		 * @param int $save_action ID da ação a ser executada na página AJAX que salva um novo item.
		 * @param int $min_length Quantidade mínima de caracteres necessária para sugerir as opções.
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array(), $source = array(), $require = false, $multiple = false, $save_action = 1, $min_length = 1){
			parent::__construct($name, $label, $value, $attributes);
			
			$this->source = $source;
			$this->require = $require;
			$this->multiple = $multiple;
			$this->save_action = $save_action;
			$this->min_length = (int)$min_length;
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			global $sys_assets;
			
			if(!is_array($this->value))
				$this->value = !empty($this->value) ? array($this->value) : array();
			
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					//Carrega os recursos necessários
					$sys_assets->load('css', 'app/assets/js/jquery/ui/theme/css/jquery.ui.all.css');
					$sys_assets->load('css', 'app/assets/js/jquery/ui/autocomplete/css/jquery.ui.autocomplete.css');
					$sys_assets->load('css', 'app/assets/js/jquery/ui/autocomplete/css/jquery.ui.menu.css');

					$sys_assets->load('js', 'app/assets/js/jquery/ui/jquery.ui.core.min.js');
					$sys_assets->load('js', 'app/assets/js/jquery/ui/jquery.ui.widget.min.js');
					$sys_assets->load('js', 'app/assets/js/jquery/ui/autocomplete/jquery.ui.menu.min.js');
					$sys_assets->load('js', 'app/assets/js/jquery/ui/autocomplete/jquery.ui.position.min.js');
					$sys_assets->load('js', 'app/assets/js/jquery/ui/autocomplete/jquery.ui.autocomplete.min.js');
					
					//HTML
					$input_id = $this->id.'_autocomplete';
					$input_value = $hidden_inputs = $items_html = '';
					
					if(sizeof($this->value)){
						$source = $this->get_source_array();
						
						if($this->multiple){
							foreach($this->value as $value){
								$hidden_inputs .= '<input type="hidden" name="'.$this->name.'[]" value="'.$value.'" />';
								
								$items_html .= '
									<span data-value="'.$value.'">
										'.$source[$value].'
										<a href="#" class="remove">Remover</a>
									</span>
								';
							}
						}
						else{
							$value = reset($this->value);
							$input_value = $source[$value];
							$hidden_inputs = '<input type="hidden" name="'.$this->name.'" value="'.$value.'" />';
						}
					}
					
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label autocomplete">
							<span class="label-title">'.$this->label.$this->label_complement.'</span>
							<input type="text" id="'.$input_id.'" value="'.$input_value.'" '.\UI\HTML::prepare_attr($this->attributes).' />
							'.$hidden_inputs.'
							<div class="items-container">'.$items_html.'</div>
							'.$this->get_tip().'
						</div>
					';
					
					//Script
					if(is_array($this->source)){
						$source = array();
						
						foreach($this->source as $value => $label)
							$source[] = array('value' => $value, 'label' => $label);
						
						$source = json_encode($source);
					}
					else{
						$source = '
							function(request, response){
								$.ajax({
									url: "'.$this->source.'",
									type: "get",
									data: {term: request.term},
									success: function(data){
										response($.parseJSON(data));
									}
								});
							}
						';
					}
					
					if($this->multiple){
						$select_script = '
							if(!$(this).parent().find("input[value=\'" + ui.item.value + "\']").length)
								$(this).parent().append("<input type=\'hidden\' name=\''.$this->name.'[]\' value=\'" + ui.item.value + "\' />").find(".items-container").append("<span data-value=\'" + ui.item.value + "\'>" + ui.item.label + " <a href=\'#\' class=\'remove\'>Remover</a></span>");
							
							$(this).val("");
							return false;
						';
						
						if(!$this->require){
							$append_script = '
								//Insere novos itens
								$("#'.$input_id.'").keypress(function(e){
									if(e.which == 13){
										var item = $.trim($(this).val());
										
										if(item.length > 0){
											var self = $(this);
											Ajax.toggle_loader(true);
											
											$.post("app/core/util/ajax/handler?page=autocomplete&save=1&a='.$this->save_action.'", {item: item}, function(response){
												if(response.success){
													self.parent().append("<input type=\'hidden\' name=\''.$this->name.'[]\' value=\'" + response.id + "\' />").find(".items-container").append("<span data-value=\'" + response.id + "\'>" + item + " <a href=\'#\' class=\'remove\'>Remover</a></span>");
													self.val("");
												}
												else{
													Ajax.result_message("error", response.error);
												}
												
												Ajax.toggle_loader(false);
											}, "json");
										}
										
										return false;
									}
								});
							';
						}
					}
					else{
						$select_script = '
							$(this).parent().find("input[name=\''.$this->name.'\']").remove().end().append("<input type=\'hidden\' name=\''.$this->name.'\' value=\'" + ui.item.value + "\' />");
							$(this).val(ui.item.label);
							
							return false;
						';
					}
					
					if($this->require){
						if($this->multiple){
							$require_script = '
								if(!ui.content.length)
									$(this).val("");
							';
						}
						else{
							$require_script = '
								if(!ui.content.length){
									$(this).val("");
									$(this).parent().find("input[name=\''.$this->name.'\']").remove();
								}
							';
						}
					}
					
					$this->script = '
						$(document).ready(function(){
							//Autocomplete
							$("#'.$input_id.'").autocomplete({
								autoFocus: true,
								source: '.$source.',
								minLength: '.$this->min_length.',
								response: function(event, ui){
									'.$require_script.'
								},
								select: function(event, ui){
									'.$select_script.'
								},
								focus: function(event, ui){
									return false;
								},
								open: function(event, ui){
									var data = $(this).data("autocomplete");
									
									data.menu.element.find("a").each(function(){
										var self = $(this);
										var keywords = data.term.split(" ").join("|");
										self.html(self.text().replace(new RegExp("(" + keywords + ")", "gi"), "<span class=\'ui-autocomplete-match\'>$1</span>"));
									});
								}
							}).keyup(function(){
								if(!$.trim($(this).val()).length)
									$(this).parent().find("input[name=\''.$this->name.'\']").remove();
							});
							
							//Remove itens
							$("#label-'.$this->id.' .items-container .remove").live("click", function(){
								var value = $(this).parent().data("value");
								
								$("#label-'.$this->id.' input[value=\'" + value + "\']").remove();
								$(this).parent().remove();
								
								return false;
							});
							
							'.$append_script.'
						});
					';
					
					break;

				case 'view':
					$content = '';
					
					if(sizeof($this->value)){
						$source = $this->get_source_array();
						$labels = array();
						
						foreach($this->value as $value)
							$labels[] = $source[$value];
						
						$content = \Util\ArrayUtil::count_items($labels);
					}
					
					$this->html = $this->view($content);
					break;
			}
		}
		
		/**
		 * Carrega o vetor de opções.
		 * 
		 * @return array Vetor de opções.
		 */
		private function get_source_array(){
			if(!is_array($this->source)){
				$source = array();
				$suggestions = json_decode(\Storage\File::grab(BASE.'/'.\URL\URL::add_params(ltrim($this->source, '/'), array('all' => 1))));
				
				if(sizeof($suggestions)){
					foreach($suggestions as $suggestion)
						$source[$suggestion->value] = $suggestion->label;
				}
				
				return $source;
			}
			
			return $this->source;
		}
		
		/**
		 * @see Field::set_value()
		 */
		public function set_value($value, $index = null){
			$this->value = $value;
		}
		
		/*-- Validação --*/
		
		/**
		 * @see FieldValidator::is_empty()
		 */
		public function is_empty(){
			global $sys_language;
			
			$value = $this->value;
			$invalid = is_array($value) ? !sizeof($value) : empty($value);
			
			if($invalid)
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_empty'), '<strong rel="'.$this->id.'">'.$this->label.'</strong>'));
			
			return self::valid();
		}
	}
?>