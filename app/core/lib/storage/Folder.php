<?php
	namespace Storage;
	
	/**
	 * Classe com métodos diversos para manipulação de pastas.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 16/10/2012
	*/
	
	abstract class Folder{
		/**
		 * Corrige um caminho de pasta se não tiver barra ('/') no início e no final.
		 * 
		 * @param string $path Caminho a ser corrigido.
		 */
		public static function fix_path(&$path){
			$path = '/'.trim($path, '/').'/';
		}
		
		/**
		 * Remove os caminhos relativos ('/', './', '../').
		 * 
		 * @param string $path Caminho a ser corrigido.
		 * @return string Caminho corrigido.
		 */
		public static function strip_relative_path($path){
			while(in_array(substr($path, 0, 1), array('/', '.')))
				$path = substr($path, 1, strlen($path) - 1);
			
			return '/'.rtrim($path, '/');
		}
		
		/**
		 * Cria uma pasta.
		 * 
		 * @param string $path Caminho da pasta a ser criada.
		 * @param int $mode Permissão CHMOD da pasta.
		 * @return boolean TRUE em caso de sucesso (ou da pasta já existir) ou FALSE em caso de falha.
		 */
		public static function create($path, $mode = 0755){
			self::fix_path($path);
			
			if(!is_dir(ROOT.$path)){
				$path_pieces = explode('/', $path);
				$mode = '0'.$mode;
				$aux_path = '';
				
				foreach($path_pieces as $path_piece){
					if(!empty($path_piece)){
						$aux_path .= '/'.$path_piece;
						
						if(!is_dir(ROOT.$aux_path))
							return @mkdir(ROOT.$aux_path, (int)$mode);
					}
				}
			}
			
			return true;
		}
		
		/**
		 * Apaga uma pasta.
		 * 
		 * @param string $path Caminho da pasta a ser apagada.
		 */
		public static function delete($path){
			self::fix_path($path);
			$folder_content = self::scan($path);
			
			foreach($folder_content->files as $file)
				\Storage\File::delete($path, $file);
			
			foreach($folder_content->folders as $folder)
				self::delete($path.$folder);
			
			return @rmdir(ROOT.$path);
		}
		
		/**
		 * Escaneia o conteúdo de uma pasta.
		 * 
		 * @param string $path Caminho da pasta a ser escaneada.
		 * @return stdClass Objeto com os atributos 'files', que contém um vetor com a lista de arquivos da pasta; e 'folders', que contém um vetor com a lista de diretórios da pasta. 
		 */
		public static function scan($path){
			self::fix_path($path);
			
			$content = new \stdClass();
			$content->files = array();
			$content->folders = array();
			
			if(is_dir(ROOT.$path)){
				$objects = scandir(ROOT.$path);
				
				//Pula '.' e '..'
				for($i = 2; $i < sizeof($objects); $i++){
					if(is_file(ROOT.$path.$objects[$i]))
						$content->files[] = $objects[$i];
					elseif(is_dir(ROOT.$path.$objects[$i]))
						$content->folders[] = $objects[$i];
				}
			}
			
			return $content;
		}
		
		/**
		 * Verifica se a pasta existe.
		 * 
		 * @param string $path Caminho da pasta a ser verificada.
		 * @return boolean TRUE caso a pasta exista ou FALSE caso a pasta não exista.
		 */
		public static function exists($path){
			$path = str_replace(ROOT, '', $path);
			return is_dir(ROOT.'/'.ltrim($path, '/'));
		}
	}
?>