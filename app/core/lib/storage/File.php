<?php
	namespace Storage;
	
	/**
	 * Classe com métodos diversos para manipulação de arquivos.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 10/04/2014
	*/
	
	abstract class File{
		/**
		 * Cria um novo arquivo.
		 * 
		 * @param string $path Pasta onde o arquivo deve ser gravado.
		 * @param string $name Nome do arquivo a ser criado.
		 * @param string $content Conteúdo do arquivo.
		 * @param boolean $overwrite Define se o arquivo deve ser substituído caso ele já exista.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function create($path, $name, $content = '', $overwrite = true){
			Folder::fix_path($path);
			$filename = ROOT.$path.$name;
			
			if(!self::exists($path.$name)){
				if(Folder::create($path)){
					$file_handler = @fopen($filename, 'w');
					fclose($file_handler);
					
					if(!empty($content))
						file_put_contents($filename, $content);
					
					return $file_handler;
				}
				
				return false;
			}
			elseif($overwrite){
				return file_put_contents($filename, $content);
			}
			
			return false;
		}
		
		/**
		 * Apaga um arquivo.
		 * 
		 * @param string $path Pasta onde o arquivo está localizado.
		 * @param string $file Nome do arquivo a ser apagado.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function delete($path, $file){
			Folder::fix_path($path);
			
			if(self::exists($path.$file))
				return @unlink(ROOT.$path.$file);
			
			return false;
		}
		
		/**
		 * Carrega o conteúdo de um arquivo.
		 * 
		 * @param string $path Pasta onde o arquivo está localizado.
		 * @param string $file Nome do arquivo a ser carregado.
		 * @return string|boolean Conteúdo do arquivo em caso de sucesso e FALSE em caso de falha.
		 */
		public static function load($path, $file){
			Folder::fix_path($path);
			
			if(self::exists($path.$file))
				return file_get_contents(ROOT.$path.$file);
			
			return false;
		}
		
		/**
		 * Grava dados em um arquivo.
		 * 
		 * @param string $path Pasta onde o arquivo está localizado.
		 * @param string $file Nome do arquivo onde os dados serão gravados.
		 * @param string $data Dados a serem inseridos.
		 * @param boolean $append Define se o conteúdo deve ser acrescentado ao arquivo, mantendo o conteúdo anterior.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function put($path, $file, $data, $append = false){
			Folder::fix_path($path);
			
			if(self::exists($path.$file)){
				if($append)
					$data = self::load($path, $file).$data;
				
				return file_put_contents(ROOT.$path.$file, $data);
			}
			else{
				return self::create($path, $file, $data);
			}
		}
		
		/**
		 * Renomeia um arquivo.
		 * 
		 * @param string $path Pasta onde o arquivo está localizado.
		 * @param string $file Nome do arquivo a ser renomeado.
		 * @param string $name Novo nome do arquivo.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function rename($path, $file, $name){
			Folder::fix_path($path);
			return rename(ROOT.$path.$file, ROOT.$path.$name);
		}
		
		/**
		 * Copia um arquivo.
		 * 
		 * @param array $from Vetor com os índices 'path', que indica a pasta onde o arquivo está localizado, e 'file', que indica o nome do arquivo a ser copiado.
		 * @param array $to Vetor com os índices 'path', que indica a pasta para qual o arquivo deve ser copiado; e 'file', que indica um novo nome para o arquivo copiado (se não preenchido, mantém o nome original).
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 * @example File::copy(array('path' => '/pasta', 'file' => 'arquivo.txt'), array('path' => '/nova-pasta', 'file' => 'novo-arquivo.txt'));
		 */
		public static function copy($from, $to){
			Folder::fix_path($from['path']);
			Folder::fix_path($to['path']);
			
			if(!$to['file'])
				$to['file'] = $from['file'];
			
			return copy(ROOT.$from['path'].$from['file'], ROOT.$to['path'].$to['file']);
		}
		
		/**
		 * Move um arquivo.
		 * 
		 * @param array $from Vetor com os índices 'path', que indica a pasta onde o arquivo está localizado, e 'file', que indica o nome do arquivo a ser movido.
		 * @param array $to Vetor com os índices 'path', que indica a pasta para qual o arquivo deve ser movido; e 'file', que indica um novo nome para o arquivo movido (se não preenchido, mantém o nome original).
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 * @example File::move(array('path' => '/pasta', 'file' => 'arquivo.txt'), array('path' => '/nova-pasta', 'file' => 'novo-arquivo.txt'));
		 */
		public static function move($from, $to){
			Folder::fix_path($from['path']);
			Folder::fix_path($to['path']);
			
			if(!$to['file'])
				$to['file'] = $from['file'];
			
			return rename(ROOT.$from['path'].$from['file'], ROOT.$to['path'].$to['file']);
		}
		
		/**
		 * Comprime o conteúdo de um arquivo (JavaScript ou CSS).
		 * 
		 * @param string $path Pasta onde o arquivo está localizado.
		 * @param string $file Nome do arquivo a ser comprimido.
		 * @param string $type Tipo de arquivo a ser comprimido (CSS ou JS).
		 * @return string|boolean Conteúdo do arquivo comprimido em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function compress($path, $file, $type){
			if(in_array($type, array('css', 'js'))){
				$data = self::load($path, $file);
				$mode = ($type == 'js') ? 'simple' : 'full';
				
				return \Formatter\String::compress($data, $mode);
			}
			
			return false;
		}
		
		/**
		 * Captura o tamanho do arquivo formatado (em Mb ou Kb).
		 * 
		 * @param string $path Pasta onde o arquivo está localizado.
		 * @param string $file Nome do arquivo a ser verificado.
		 * @param string $format Unidade do tamanho do arquivo ('Mb' para megabytes e 'Kb' para kilobytes).
		 * @param string $decimal Caractere utilizado como separador das casas decimais.
		 * @param string $separator Caractere utilizado entre o tamanho do arquivo e sua unidade.
		 * @return string Tamanho do arquivo formatado.
		 */
		public static function size($path, $file, $format = 'Mb', $decimal = ',', $separator = ' '){
			Folder::fix_path($path);
			
			if(self::exists($path.$file)){
				$size = filesize(ROOT.$path.$file);
				
				switch(strtolower($format)){
					case 'mb': $size = (($size / 1024) / 1024); break;
					case 'kb':
					default: $size = ($size / 1024); break;
				}
			}
			
			$size = number_format($size, 1, $decimal, '');
			return $size.$separator.$format;
		}
		
		/**
		 * Captura a extensão de um arquivo.
		 * 
		 * @param string $file Nome completo do arquivo.
		 * @return string Extensão do arquivo.
		 */
		public static function extension($file){
			$info = pathinfo($file);
			return strtolower($info['extension']);
		}
		
		/**
		 * Captura o nome de um arquivo (sem sua extensão).
		 * 
		 * @param string $file Nome completo do arquivo.
		 * @return string Nome do arquivo sem extensão.
		 */
		public static function name($file){
			$info = pathinfo($file);
			return $info['filename'];
		}
		
		/**
		 * Retorna o caminho e o nome do arquivo separadamente.
		 * 
		 * @param string $file Caminho completo do arquivo.
		 * @return array Vetor com os índices 'path', que indica a pasta do arquivo; e 'file', que indica o nome do arquivo.
		 */
		public static function split_path($file){
			$info = pathinfo($file);
			return array('path' => $info['dirname'], 'file' => $info['basename']);
		}
		
		/**
		 * Verifica se o arquivo existe.
		 * 
		 * @param string $file Caminho completo do arquivo.
		 * @return boolean TRUE caso o arquivo exista ou FALSE caso o arquivo não exista.
		 */
		public static function exists($file){
			$file = str_replace(ROOT, '', $file);
			return is_file(ROOT.'/'.ltrim($file, '/'));
		}
		
		/**
		 * Captura o conteúdo de um arquivo em outro servidor.
		 * 
		 * @param string $url Endereço do arquivo a ser capturado.
		 * @param boolean $save Indica se o arquivo deve ou não ser gravado.
		 * @param string $path Pasta onde o arquivo deve ser gravado.
		 * @param string $file Nome do arquivo a ser gravado (se não preenchido, mantém o nome original).
		 * @return boolean|string Se o arquivo deve ser gravado, retorna TRUE em caso de sucesso ou FALSE em caso de falha. Senão, retorna o conteúdo do arquivo.
		 */
		public static function grab($url, $save = false, $path = '/uploads', $file = ''){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			
			$data = curl_exec($ch);
			curl_close($ch);
			
			if($save && !empty($path)){
				Folder::fix_path($path);
				
				if(empty($file)){
					$url_pieces = self::split_path($url);
					$file = $url_pieces['file'];
				}
				
				if(self::exists($path.$file))
					self::delete($path, $file);
				
				return self::put($path, $file, $data);
			}
			
			return $data;
		}
		
		/*-- Upload --*/
		
		/**
		 * Valida o nome de um arquivo para upload (verifica se já existe na pasta, tira espaços em branco, acentos e letras maiúsculas).
		 * 
		 * @param string $path Pasta onde o arquivo deve ser validado.
		 * @param string $file Nome do arquivo.
		 * @return string Novo nome do arquivo já validado.
		 */
		public static function validate($path, $file){
			Folder::fix_path($path);
			$file = \Formatter\String::strip_special_chars(self::name($file)).'.'.self::extension($file);
			
			if(self::exists($path.$file)){
				$i = 1;
				$aux_file = $file;
				
				$file_name = self::name($file);
				$file_extension = self::extension($file);
				
				while(self::exists($path.$aux_file)){
					$aux_file = $file_name.'_'.$i.'.'.$file_extension;
					$i++;
				}
				
				$file = $aux_file;
			}
			
			return $file;
		}
		
		/**
		 * Faz upload de um arquivo.
		 * 
		 * @param string $input_name Nome do campo utilizado para upload (será utilizado pela superglobal $_FILES).
		 * @param string $folder Pasta onde o arquivo será gravado.
		 * @param array $extension_whitelist Vetor que indica as extensões de arquivo permitidas para upload.
		 * @param array $extension_blacklist Vetor que indica as extensões de arquivo bloqueadas para upload.
		 * @param boolean $validate_image Define se deve validar o arquivo como imagem.
		 * @param float $max_size Tamanho máximo do arquivo em megabytes.
		 * @return array Vetor com os índices 'success', que indica TRUE em caso de sucesso e FALSE em caso de falha; 'file', que indica o nome do arquivo enviado em caso de sucesso; e 'error', que contém a mensagem de erro em caso de falha.
		 */
		public static function upload($input_name, $folder, $extension_whitelist = array(), $extension_blacklist = array(), $validate_image = false, $max_size = 0){
			global $sys_language;
			
			$success = false;
			$error = '';
			$folder = Folder::strip_relative_path($folder);
			Folder::fix_path($folder);
			
			$file = $_FILES[$input_name];
			$file_temp = $file['tmp_name'];
			$file_extension = \Storage\File::extension($file['name']);
			
			if(!$file['tmp_name']){
				$error = $sys_language->get('class_file', 'upload_select_error');
			}
			elseif((sizeof($extension_whitelist) && !in_array($file_extension, $extension_whitelist)) || (sizeof($extension_blacklist) && in_array($file_extension, $extension_blacklist))){
				$error = $sys_language->get('class_file', 'upload_extension_error');
			}
			elseif($validate_image && !\Form\Validator::is_image($file_temp, $extension_whitelist)){
				$error = $sys_language->get('class_file', 'upload_image_error');
			}
			else{
				if($max_size){
					$max_file_size_in_bytes = 1024 * 1024 * (int)$max_size;
					$file_size = @filesize($file_temp);
					
					if(!$file_size || ($file_size > $max_file_size_in_bytes))
						$error = $sys_language->get('class_file', 'upload_large_file');
				}
				
				if(!$error){
					if($file['error'] > 0){
						$error = $file['error'];
					}
					else{
						$filename = self::validate($folder, stripslashes($file['name']));
						
						if(!Folder::exists($folder))
							Folder::create($folder);
						
						if(move_uploaded_file($file_temp, ROOT.$folder.$filename))
							$success = true;
						else
							$error = $sys_language->get('class_file', 'upload_error');
					}
				}
			}
			
			return $success ? array('success' => true, 'file' => $filename) : array('success' => false, 'error' => $error);
		}
	}
?>