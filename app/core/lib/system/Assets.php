<?php
	namespace System;
	
	/**
	 * Classe que controla os componentes (CSS e JS) do sistema.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 31/03/2014
	*/
	
	class Assets{
		private $css_list;
		private $js_list;
		private $compress_displayed;
		
		/**
		 * Instancia um objeto que contém a lista de componentes do site.
		 * 
		 * @param array $css_list Vetor com os índices 'file', que indica o caminho do arquivo CSS; 'compress', que indica se o arquivo CSS deve ser comprimido ou não; e 'params', que define outros parâmetros da tag LINK.
		 * @param array $js_list Vetor com os índices 'file', que indica o caminho do arquivo JavaScript; 'compress', que indica se o arquivo JavaScript deve ser comprimido ou não; e 'params', vetor que define outros parâmetros da tag SCRIPT onde a chave é o nome do parâmetro e o valor é o valor do parâmetro.
		 */
		public function __construct($css_list = array(), $js_list = array()){
			$this->css_list = $css_list;
			$this->js_list = $js_list;
			
			$this->compress_displayed = array(
				'js' => false,
				'css' => false
			);
		}
		
		/**
		 * Limpa a lista de componentes.
		 */
		public function clear(){
			$this->css_list = $this->js_list = array();
			$this->compress_displayed = false;
		}
		
		/**
		 * Adiciona um novo recurso à lista.
		 * 
		 * @param string $type Tipo do recurso, que pode ser 'CSS' ou 'JS'.
		 * @param array $asset Vetor com os índices 'file', que indica o caminho do arquivo; 'compress', que indica se o arquivo deve ser comprimido ou não; e 'params', vetor que define outros parâmetros da tag onde a chave é o nome do parâmetro e o valor é o valor do parâmetro.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function add($type, $asset = array()){
			switch(strtolower($type)){
				case 'css':
					$this->css_list[] = $asset;
					break;
				
				case 'js':
					$this->js_list[] = $asset;
					break;
				
				default:
					return false;
					break;
			}
			
			return true;
		}
		
		/**
		 * Carrega um novo recurso em tempo de execução.
		 * 
		 * @param string $type Tipo do recurso, que pode ser 'CSS' ou 'JS'.
		 * @param string|array $file Caminho completo do arquivo de recurso ou vetor contendo uma lista de arquivos de recurso.
		 * @param array $params Vetor que define outros parâmetros da tag onde a chave é o nome do parâmetro e o valor é o valor do parâmetro.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function load($type, $file, $params = array()){
			if(is_array($file) && sizeof($file)){
				foreach($file as $file_item)
					$this->load($type, $file_item);
				
				return true;
			}
			
			if(!\Storage\File::exists($file))
				return false;
			
			$type = strtolower($type);
			
			if($type == 'css')
				$list = $this->css_list;
			elseif($type == 'js')
				$list = $this->js_list;
			else
				return false;
			
			foreach($list as $item){
				if($item['file'] == $file)
					return false;
			}
			
			return $this->add($type, array('file' => $file, 'compress' => false, 'params' => $params));
		}
		
		/**
		 * Carrega os recursos e monta o HTML com as tags necessárias.
		 * 
		 * @param string $area Área do site que está utilizando os recursos, que pode ser 'admin' para a área administrativa e 'site' para o site.
		 * @param string $type Tipo de recursos, que pode ser 'CSS' ou 'JS'.
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public function display($area, $type, $echo = true){
			if(in_array($area, array('admin', 'site')) && in_array($type, array('css', 'js'))){
				$compressed_items = array();
				$detailed_compressed_items = array();
				$common_html = '';
				$compress_file_content = '';
				$recompress = false;
				
				if($type == 'js'){
					foreach($this->js_list as $index => $js_item){
						if(!$js_item['displayed']){
							if($js_item['compress'] && \HTTP\Server::is_web_server())
								$compressed_items[] = $js_item['file'];
							else
								$common_html .= "<script src='".$js_item['file']."' ".\UI\HTML::prepare_attr($js_item['params']).">".$js_item['content']."</script>\n";

							$this->js_list[$index]['displayed'] = true;
						}
					}
				}
				elseif($type == 'css'){
					foreach($this->css_list as $index => $css_item){
						if(!$css_item['displayed']){
							if($css_item['compress'] && \HTTP\Server::is_web_server())
								$compressed_items[] = $css_item['file'];
							else
								$common_html .= "<link href='".$css_item['file']."' rel='stylesheet' ".\UI\HTML::prepare_attr($css_item['params'])." />\n";

							$this->css_list[$index]['displayed'] = true;
						}
					}
				}
				
				$resource_list_file = \Storage\File::load('/app/assets/compress/'.$area, $type.'_files');
				$resource_files = explode("\n", $resource_list_file);
				
				foreach($compressed_items as $compressed_item){
					if(\Storage\File::exists($compressed_item)){
						$item = $compressed_item.' '.date('d/m/Y H:i:s', filemtime(ROOT.'/'.ltrim($compressed_item, '/')));
						$compress_file_content .= $item."\n";
						$detailed_compressed_items[] = $item;
						
						if(!$recompress){
							if(!in_array($item, $resource_files)){
								$recompress = true;
								continue;
							}
							
							$curr_file_pieces = explode(' ', $item);
							$curr_file_name = $curr_file_pieces[0];
							$curr_file_timestamp = $curr_file_pieces[1].' '.$curr_file_pieces[2];
							
							foreach($resource_files as $resource_file){
								if(!empty($resource_file)){
									$file_record_pieces = explode(' ', $resource_file);
									$rec_file_name = $file_record_pieces[0];
									$rec_file_timestamp = $file_record_pieces[1].' '.$file_record_pieces[2];
									
									if(($rec_file_name == $curr_file_name) && ($rec_file_timestamp != $curr_file_timestamp))
										$recompress = true;
								}
							}
						}
					}
				}
				
				if(!$recompress){
					foreach($resource_files as $resource_file){
						if(!empty($resource_file)){
							if(!in_array($resource_file, $detailed_compressed_items)){
								$recompress = true;
								break;
							}
						}
					}
				}
				
				if(!$this->compress_displayed[$type]){
					if($recompress){
						\Storage\File::put('/app/assets/compress/'.$area, $type.'_files', $compress_file_content);
						$compressed_data = '';

						foreach($compressed_items as $compressed_item){
							$item_path = \Storage\File::split_path($compressed_item);
							$compressed_data .= \Storage\File::compress($item_path['path'], $item_path['file'], $type);
						}

						\Storage\File::put('/app/assets/compress/'.$area, 'compress.'.$type, $compressed_data);
					}
					
					$compress_html = ($type == 'js') ? '<script src="app/assets/compress/'.$area.'/compress.js"></script>' : '<link href="app/assets/compress/'.$area.'/compress.css" rel="stylesheet" />';
					$this->compress_displayed[$type] = true;
				}
				
				if($echo)
					echo $compress_html."\n".$common_html;
				else
					return $compress_html."\n".$common_html;
			}
		}
	}
?>