<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo de senha.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 08/04/2014
	*/
	
	class Password extends Field{
		protected $show_strength;
		protected $require_previous;
		protected $field_on_edit;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param string $value Valor do campo.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param boolean $show_strength Define se deve ser exibida a força da senha digitada.
		 * @param boolean $require_previous Define se, ao alterar a senha, deve ser solicitada a senha anterior para completar o processo.
		 * @param boolean $field_on_edit Define se, o formulário estiver em modo de edição e a senha anterior não for necessária, o campo para preenchimento de senha deve ser exibido diretamente ao invés do link de alteração.
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array(), $show_strength = true, $require_previous = true, $field_on_edit = false){
			parent::__construct($name, $label, $value, $attributes);
			
			$this->show_strength = $show_strength;
			$this->require_previous = $require_previous;
			$this->field_on_edit = $field_on_edit;
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			global $sys_assets, $sys_language;
			
			if(($this->form->get_mode() == 'insert') || (($this->form->get_mode() == 'edit') && !$this->require_previous && $this->field_on_edit)){
				$this->html = '
					<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
						<span class="label-title">'.$this->label.$this->label_complement.'</span>
						<input type="password" name="'.$this->name.'" id="'.$this->id.'" '.\UI\HTML::prepare_attr($this->attributes).' />
				';
				
				$this->script = '';

				//Força de senha
				if($this->show_strength){
					$sys_assets->load('css', 'app/assets/js/password-strength-meter/styles.css');
					$sys_assets->load('js', 'app/assets/js/password-strength-meter/script.js');

					$this->html .= '
						<div class="password-strength">
							<span class="score"></span>
							<span class="progress"><span class="bar"></span></span>
						</div>
					';

					$this->script .= '
						//Força de senha
						$("#'.$this->id.'").keyup(function(){
							var strength_result = PasswordStrength.check($(this).val());
							var strength_container = $(this).parent().find(".password-strength");

							if($(this).val().length > 0)
								strength_container.find(".score").text(strength_result.strength.text).end().show().find(".progress > .bar").removeClass().addClass("bar " + strength_result.strength.class_name).css("width", strength_result.score + "%");
							else
								strength_container.hide().find(".score").text("").end().find(".progress > .bar").css("width", "0%");
						});
					';
				}

				$this->html .= '
						'.$this->get_tip().'
					</div>
				';
			}
			elseif($this->form->get_mode() == 'edit'){
				$sys_assets->load('css', 'app/assets/js/jquery/plugins/fancybox/jquery.fancybox.css');
				$sys_assets->load('js', 'app/assets/js/jquery/plugins/fancybox/jquery.fancybox.pack.js', array('charset' => 'ISO-8859-1'));

				$this->html = '
					<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
						<span class="label-title">'.$this->label.'</span>
						<a href="#" class="icon password-edit" id="'.$this->id.'_edit">'.$sys_language->get('class_form', 'change_password').'</a>
						'.$this->get_tip().'
					</div>
				';

				$prev = $this->require_previous ? 1 : 0;

				$this->script = '
					//Janela de alteração de senha
					$("#'.$this->id.'_edit").fancybox({
						href: "app/core/util/modal/wrapper?page=password-change&table='.$this->form->get_table().'&field='.$this->id.'&id='.$this->form->get_record_id().'&prev='.$prev.'",
						type: "iframe",
						width: 600,
						padding: 5,
						helpers: {
							overlay: {
								closeClick: false
							}
						}
					});
				';
			}
		}
		
		/*-- Validação --*/
		
		/**
		 * @see FieldValidator::is_empty()
		 */
		public function is_empty(){
			if(($this->form->get_mode() == 'insert') || ($this->field_on_edit))
				return parent::is_empty();
			
			return self::valid();
		}
	}
?>