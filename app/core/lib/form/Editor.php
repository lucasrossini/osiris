<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo de editor de texto.
	 * 
	 * @package Osiris
	 * @uses CKEditor; CKFinder
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 04/04/2014
	*/
	
	class Editor extends Field{
		protected $maxlength;
		protected $settings;
		protected $height;
		
		private static $toolbar_types = array(
			'simple' => array(array('Bold', 'Italic', 'Underline', 'Strike', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink', '-', 'Undo', 'Redo', '-', 'PasteFromWord')),
			'full' => array(array('Format', '-', 'Bold', 'Italic', 'Underline', 'Strike', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink', '-', 'Image', 'Flash', 'Table', '-', 'Undo', 'Redo', '-', 'PasteFromWord', '-', 'Source'))
		);
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param string $value Valor do campo.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param int $maxlength Limite de caracteres do campo (0 para ilimitado).
		 * @param array $settings Vetor de configurações com o índice 'toolbar', que possui o tipo de barra de ferramentas; e 'pastefix', que indica se, ao colar um texto, sua formatação seja limpada.
		 * @param int $height Altura do editor.
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array(), $maxlength = 0, $settings = array('toolbar' => 'full', 'pastefix' => true), $height = 250){
			parent::__construct($name, $label, $value, $attributes);
			
			$this->maxlength = (int)$maxlength;
			$this->settings = $settings;
			$this->height = (int)$height;
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			global $sys_language;
			
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					$toolbar = in_array($this->settings['toolbar'], array('simple', 'full')) ? $this->settings['toolbar'] : 'full';

					$ckeditor_config = array(
						'toolbar' => self::$toolbar_types[$toolbar],
						'height' => $this->height,
						'forcePasteAsPlainText' => $this->settings['pastefix'],
						'limit' => $this->maxlength
					);

					//CKEditor
					require_once CORE_PATH.'/lib/form/ckeditor/ckeditor.php';
					$ckeditor = new \CKEditor();
					$ckeditor->returnOutput = true;
					$ckeditor->basePath = rtrim(DIR, '/').'/app/core/lib/form/ckeditor/';
					$ckeditor->config['language'] = \System\Language::get_current_lang();

					//CKFinder
					require_once CORE_PATH.'/lib/form/ckfinder/ckfinder.php';
					$ckfinder = new \CKFinder();
					$ckfinder->BasePath = rtrim(DIR, '/').'/app/core/lib/form/ckfinder/';
					$ckfinder->SetupCKEditorObject($ckeditor);
					
					if($this->multilang){
						$hidden_inputs = '';
						
						foreach(\System\Language::get_available_languages() as $code => $language)
							$hidden_inputs .= '<input type="hidden" name="'.$this->id.'['.$code.']" value="'.$this->value[$code].'" />';
						
						$editor_value = $this->value[\System\Language::get_current_lang()];
					}
					else{
						$hidden_inputs = '';
						$editor_value = $this->value;
					}
					
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
							<span class="label-title">'.$this->label.$this->label_complement.'</span>
							'.$ckeditor->editor($this->id, $editor_value, $ckeditor_config).'
					';
					
					$this->script = '$("textarea[name=\''.$this->name.'\']").addClass("ckeditor");';

					//Limite de caracteres
					if($this->maxlength){
						$chars = !$editor_value ? $this->maxlength : ($this->maxlength - strlen(\Formatter\String::remove_line_breaks(html_entity_decode(strip_tags($editor_value)))));

						if($chars < 0)
							$chars = 0;

						$this->html .= '<span class="char-count"><span class="chars-left" id="'.$this->id.'_chars_left">'.$chars.'</span> '.$sys_language->get('class_form', 'chars_left').'</span>';

						$this->script .= '
							//Contagem de caracteres no CKEditor
							CKEDITOR.instances["'.$this->id.'"].on("change", function(e){
								var count = e.editor.getData().replace(/<[^>]*>/g, "").replace(/\s+/g, " ").replace(/&\w+;/g ,"X").replace(/^\s*/g, "").replace(/\s*$/g, "").length;
								$("#'.$this->id.'_chars_left").text(('.$this->maxlength.' - count) >= 0 ? '.$this->maxlength.' - count : 0);

								if(count >= '.$this->maxlength.'){
									e.editor.fire("saveSnapshot");
									e.cancel();

									if(!e.editor.config.locked){
										e.editor.config.locked = 1;
									}
									else{
										setTimeout(function(){
											if(count > '.$this->maxlength.')
												e.editor.execCommand("undo");
											else
												e.editor.config.locked = 0;
										}, 0);
									}
								}
							});
						';
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
							var current_lang = $("#'.$this->id.'-language-selector ul li.current").data("lang");
							
							$("#'.$this->id.'-language-selector ul li").click(function(){
								var new_lang = $(this).data("lang");
								CKEDITOR.instances.'.$this->id.'.setData($("input[name=\''.$this->id.'[" + new_lang + "]\']").val());
								current_lang = new_lang;
							});
							
							CKEDITOR.instances["'.$this->id.'"].on("change", function(e){
								$("input[name=\''.$this->id.'[" + current_lang + "]\']").val(e.editor.getData());
							});
						';
					}
					
					break;

				case 'view':
					//Carrega os recursos
					global $sys_assets;
					
					$sys_assets->load('css', 'app/assets/js/jquery/plugins/fancybox/jquery.fancybox.css');
					$sys_assets->load('js', 'app/assets/js/jquery/plugins/fancybox/jquery.fancybox.pack.js', array('charset' => 'ISO-8859-1'));
					$sys_assets->load('js', 'app/assets/js/image.js');
					
					$this->html = $this->view('<div class="editor-content clearfix">'.$this->value.'</div>');
					$this->script = 'Image.apply_subtitles($(".editor-content"), 300);';
					
					break;
			}
		}
	}
?>