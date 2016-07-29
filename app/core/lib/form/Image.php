<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo de upload de imagem com recorte.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 08/04/2014
	*/
	
	class Image extends Field{
		const MAX_WIDTH = 600;
		
		protected $folder;
		protected $dimensions;
		protected $prefix;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param string $value Valor do campo.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param string $folder Pasta onde o arquivo enviado será gravado.
		 * @param array $dimensions Vetor com as dimensões desejadas da imagem resultante, onde o índice 'width' indica o comprimento da imagem em pixels e o índice 'height' indica a altura da imagem em pixels.
		 * @param string $prefix Prefixo no nome do arquivo de imagem gerado após o recorte.
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array(), $folder = '/uploads', $dimensions = array('width' => 150, 'height' => 150), $prefix = 'cr_'){
			parent::__construct($name, $label, $value, $attributes);

			\Storage\Folder::fix_path($folder);
			$this->folder = $folder;
			$this->dimensions = $dimensions;
			$this->prefix = $prefix;
			
			$this->label_complement = ' ('.$dimensions['width'].'px x '.$dimensions['height'].'px)';
			
			if(!\Storage\File::exists($this->folder.$this->value))
				$this->value = '';
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			global $sys_assets, $sys_language;
			
			$image_src = ($this->dimensions['width'] <= self::MAX_WIDTH) ? \Media\Image::source($this->folder.$this->value) : \Media\Image::thumb(\Media\Image::source($this->folder.$this->value), self::MAX_WIDTH, 0);
			
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					//Recursos necessários
					$sys_assets->load('css', 'app/assets/js/jquery/plugins/fancybox/jquery.fancybox.css');
					$sys_assets->load('js', 'app/assets/js/jquery/plugins/fancybox/jquery.fancybox.pack.js', array('charset' => 'ISO-8859-1'));
					
					//HTML
					$insert_image_html = '<div class="image-control-links"><a href="#" id="image_upload_link_'.$this->id.'" class="icon image-upload" rel="'.$this->id.'">'.$sys_language->get('class_form', 'insert').'</a></div>';
					
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
							<span class="label-title">'.$this->label.$this->label_complement.'</span>
							
							<div id="image_upload_target_'.$this->id.'" class="image-upload-target">
								<input type="hidden" name="'.$this->name.'" value="'.$this->value.'" id="image_upload_value_'.$this->id.'" />
					';
					
					if($this->value){
						$this->html .= '
							<div class="image-container"><img src="'.$image_src.'" alt="'.$this->value.'" /></div>

							<div class="image-control-links">
								<a href="#" id="image_upload_link_'.$this->id.'" class="icon image-upload">'.$sys_language->get('class_form', 'change').'</a>
								<span class="slash">/</span>
								<a href="#" id="image_remove_link_'.$this->id.'" data-folder="'.$this->folder.'" data-file="'.$this->value.'" class="icon image-remove">'.$sys_language->get('class_form', 'remove').'</a>
							</div>
						';
					}
					else{
						$this->html .= $insert_image_html;
					}
					
					$this->html .= '
							</div>
							
							'.$this->get_tip().'
						</div>
					';

					//Script
					$upload_params = array(
						'id' => $this->id,
						'width' => $this->dimensions['width'],
						'height' => $this->dimensions['height'],
						'folder' => $this->folder,
						'proportional' => 1,
						'max_width' => 760,
						'max_height' => 450,
						'prefix' => $this->prefix
					);

					$this->script = '
						//Upload de imagem
						$("#image_upload_link_'.$this->id.'").live("click", function(){
							$.fancybox({
								type: "iframe",
								href: "app/core/util/modal/wrapper?page=image-upload'.\Util\ArrayUtil::paramify($upload_params, false).'",
								autoSize: false,
								width: 800,
								height: "auto",
								padding: 5,
								scrolling: "no",
								helpers: {
									overlay: {
										closeClick: false
									}
								}
							});

							return false;
						});

						$("#image_remove_link_'.$this->id.'").live("click", function(){
							if(confirm("'.$sys_language->get('class_form', 'image_delete_confirm').'")){
								Ajax.toggle_loader(true);

								$.post("app/core/util/ajax/handler?page=image-upload&action=remove", {folder: $(this).data("folder"), file: $(this).data("file")}, function(){
									$("#image_upload_target_'.$this->id.'").find(".image-container, .image-control-links").remove().end().append("'.addslashes($insert_image_html).'");
									$("#image_upload_value_'.$this->id.'").val("");
									
									Ajax.toggle_loader(false);
								});
							}
							
							return false;
						});
					';
					
					break;

				case 'view':
					$image_obj = new \Media\Image(\Media\Image::source($this->folder.$this->value));
					$box_dimensions = ($this->dimensions['width'] > 600) ? $image_obj->get_resize_dimensions(600, 0) : $this->dimensions;

					$content = '
						<div class="image-container" style="width: '.$box_dimensions['width'].'px; height: '.$box_dimensions['height'].'px">
							<img src="'.$image_src.'" alt="'.$this->value.'" />
						</div>
					';
					
					$this->html = $this->view($content);
					break;
			}
		}
	}
?>