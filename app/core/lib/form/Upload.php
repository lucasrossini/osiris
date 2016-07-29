<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo de upload de arquivos.
	 * 
	 * @package Osiris
	 * @uses Uploadifive
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 11/04/2014
	*/
	
	class Upload extends Field{
		protected $folder;
		protected $auto;
		protected $max_files;
		protected $allowed_extensions;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param array $value Nome dos arquivos.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param string $folder Pasta onde os arquivos enviados serão gravados.
		 * @param boolean $auto Define se o upload deve ser iniciado automaticamente.
		 * @param int $max_files Quantidade máxima de arquivos permitida para envio (0 para sem limite).
		 * @param array $allowed_extensions Vetor com as extensões de arquivo permitidas para upload (vazio para permitir qualquer extensão).
		 */
		public function __construct($name, $label = '', $value = array(), $attributes = array(), $folder = '/uploads', $auto = true, $max_files = 0, $allowed_extensions = array()){
			parent::__construct($name, $label, $value, $attributes);

			\Storage\Folder::fix_path($folder);
			$this->folder = $folder;
			$this->auto = $auto;
			$this->max_files = (int)$max_files;
			$this->allowed_extensions = $allowed_extensions;
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
					$sys_assets->load('css', 'app/core/lib/form/uploadifive/css/uploadifive.css');
					$sys_assets->load('js', 'app/core/lib/form/uploadifive/js/jquery.uploadifive.min.js');
					
					//HTML
					$queue_id = $this->id.'_file_upload_queue';
					$info = '';
					
					//Múltiplos arquivos
					$multiple = ($this->max_files !== 1);
					
					if($multiple && $this->max_files)
						$info .= sprintf($sys_language->get('class_form', 'max_files'), $this->max_files).' ';
					
					if(sizeof($this->allowed_extensions))
						$info .= '('.\Util\ArrayUtil::count_items($this->allowed_extensions).')';
					
					if(!empty($info))
						$info = '<span class="info">* '.trim($info).'</span>';
					
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label multiple-items-container">
							'.$info.'
							<span class="label-title">'.$this->label.$this->label_complement.'</span>
							<input type="file" name="'.$this->id.'_file_upload" id="'.$this->id.'_file_upload" />
								
							<div id="'.$queue_id.'" class="uploadifive-queue">
					';
					
					if(sizeof($this->value)){
						foreach($this->value as $file){
							if(\Storage\File::exists($this->folder.$file)){
								$this->html .= '
									<div class="uploadifive-queue-item complete">
										<a href="#" class="close uploadifive-delete" data-file="'.$file.'">X</a>
										<span class="filename">'.$file.' ('.\Storage\File::size($this->folder, $file, 'Kb').')</span>
										<input type="hidden" name="'.$this->name.'[]" value="'.$file.'" />
									</div>
								';
							}
						}
					}
					
					$actions_html = '';
					
					if(!$this->auto){
						$actions_html = '
							<div class="uploadifive-actions">
								<a href="#" data-action="upload" class="action upload">'.$sys_language->get('class_form', 'send_files').'</a>
								/
								<a href="#" data-action="clearQueue" class="action clear-queue">'.$sys_language->get('class_form', 'clear_queue').'</a>
							</div>
						';
					}
					
					$this->html .= '
								<p class="drop-message">Solte os arquivos para enviar...</p>
							</div>
							
							'.$actions_html.'
							'.$this->get_tip().'
						</div>
					';
					
					//Token
					$timestamp = time();
					$token = md5(KEY.$timestamp);
					
					$this->script = '
						//Upload de arquivos
						$("#'.$this->id.'_file_upload").uploadifive({
							uploadScript: "'.rtrim(DIR, '/').'/app/core/util/ajax/handler?page=uploadifive&action=upload",
							auto: '.\Formatter\String::bool2string($this->auto).',
							multi: '.\Formatter\String::bool2string($multiple).',
							formData: {
								folder: "'.$this->folder.'",
								timestamp: "'.$timestamp.'",
								token: "'.$token.'",
								whitelist: "'.addslashes(serialize($this->allowed_extensions)).'"
							},
							queueID: "'.$queue_id.'",
							buttonText: "'.$sys_language->get('class_form', 'select_files').'",
							fileObjName: "file",
							width: 120,
							removeCompleted: false,
							queueSizeLimit: '.$this->max_files.',
							onError: function(code, file){
								Ajax.result_message("error", code);
								$("#'.$this->id.'_file_upload_queue > .uploadifive-queue-item:not(.complete)").remove();
							},
							onUploadComplete: function(file, data){
								data = $.parseJSON(data);

								if(data.success){
									file.queueItem.find(".close").unbind("click").attr("title", "'.$sys_language->get('class_form', 'delete_file').'").addClass("uploadifive-delete").data("file", data.file).end().find(".filename").text(data.description);
									file.queueItem.append("<input type=\'hidden\' name=\''.$this->name.'[]\' value=\'" + data.file + "\' />");
								}
								else{
									Ajax.result_message("error", data.error);
									$("#'.$this->id.'_file_upload").uploadifive("cancel", file, true);
								}
							}
						});
						
						$("#label-'.$this->id.' .uploadifive-actions > .action").click(function(){
							$("#'.$this->id.'_file_upload").uploadifive($(this).data("action"));
							return false;
						});

						//Apaga arquivos
						$("#'.$this->id.'_file_upload_queue .uploadifive-delete").live("click", function(){
							var trigger = $(this);

							if(confirm("'.$sys_language->get('class_form', 'file_delete_confirm').'")){
								Ajax.toggle_loader(true);
								trigger.hide();

								$.post("app/core/util/ajax/handler?page=uploadifive&action=remove", {timestamp: "'.$timestamp.'", token: "'.$token.'", folder: "'.$this->folder.'", file: trigger.data("file")}, function(response){
									if(response.success){
										var queue_item = trigger.parents(".uploadifive-queue-item:first");
										var file = trigger.parents(".uploadifive-queue-item:first").data("file");
										
										queue_item.remove();
										
										if(file)
											$("#'.$this->id.'_file_upload").uploadifive("cancel", file, true);
									}
									else{
										Ajax.result_message("error", response.error);
										trigger.show();
									}
									
									Ajax.toggle_loader(false);
								}, "json");
							}
							
							return false;
						});
						
						//Drag & drop
						/*$(document).on("dragover", function(e){
							var dt = e.originalEvent.dataTransfer;
							
							if((dt.types != null) && (dt.types.indexOf ? (dt.types.indexOf("Files") != -1) : (dt.types.contains("application/x-moz-file")))){
								$("#'.$queue_id.'").addClass("dropzone");
								clearTimeout(drag_timer);
							}
						});

						$(document).on("dragleave", function(e){
							if((e.originalEvent.pageX != 0) || (e.originalEvent.pageY != 0))
								return;
							
							var queue = $("#'.$queue_id.'");
							queue.removeClass("dropzone").find(".drop-message").appendTo(queue);
						});*/
					';
					
					//Re-ordenação de arquivos
					if($multiple){
						$sys_assets->load('js', 'app/assets/js/jquery/ui/jquery.ui.core.min.js');
						$sys_assets->load('js', 'app/assets/js/jquery/ui/jquery.ui.widget.min.js');
						$sys_assets->load('js', 'app/assets/js/jquery/ui/sortable/jquery.ui.mouse.min.js');
						$sys_assets->load('js', 'app/assets/js/jquery/ui/sortable/jquery.ui.sortable.min.js');

						$this->script .= '
							//Re-ordenação de arquivos
							$("#'.$queue_id.'").sortable({
								items: "> .uploadifive-queue-item",
								opacity: 0.6,
								axis: "y",
								cursor: "move"
							});
						';
					}
					
					break;

				case 'view':
					$content = '';
					$files_count = sizeof($this->value);
					
					if($files_count){
						$zip_files = array();
						$content .= '<div class="form-files-list">';

						foreach($this->value as $file){
							$zip_files[] = $this->folder.$file;
							$content .= '<a href="'.\Storage\Download::link($this->folder, $file).'" title="'.$sys_language->get('class_form', 'download_file').' &quot;'.$file.'&quot;" class="icon file">'.$file.'</a>';
						}

						$content .= '</div>';
						
						//Download de todos os arquivos em formato ZIP
						if($files_count > 1)
							$content .= '<a href="'.\Storage\Download::zip_link($zip_files).'" class="icon download-all">'.$sys_language->get('class_form', 'download_all_files').' (.zip)</a>';
					}
					
					$this->html = $this->view($content);
					break;
			}
		}
		
		/**
		 * @see Field::set_value()
		 */
		public function set_value($value, $index = null){
			$this->value = !is_array($value) ? array($value) : $value;
		}
	}
?>