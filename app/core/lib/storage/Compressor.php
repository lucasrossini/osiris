<?php
	namespace Storage;
	
	/**
	 * Classe para compressão/descompressão de arquivos.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 07/01/2013
	 */
	
	abstract class Compressor{
		/**
		 * Compacta arquivos em ZIP.
		 * 
		 * @param array $files Vetor com os arquivos/pastas a serem compactados.
		 * @param string $target_file Caminho do arquivo .zip a ser criado.
		 * @param boolean $overwrite Define se o arquivo a ser criado, caso já exista, deve ser substituído. Senão, os arquivos serão acrescentados ao .zip já existente.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function zip($files, $target_file, $overwrite = true){
			if(is_array($files) && sizeof($files)){
				$zip = new \ZipArchive();
				$target_file = rtrim('/'.ltrim($target_file, '/'), '.zip').'.zip';
				
				if($overwrite){
					$target_file_info = File::split_path($target_file);
					File::delete($target_file_info['path'], $target_file_info['file']);
				}

				if($zip->open(ROOT.$target_file, \ZipArchive::CREATE) !== true)
					return false;
				
				for($i = 0; $i < sizeof($files); $i++){
					$file = $files[$i];
					
					if(File::exists($file)){
						$file = ROOT.'/'.ltrim($file, '/');
						$file_info = File::split_path($file);
						
						$zip->addFile($file, $file_info['file']);
					}
					elseif(Folder::exists($file)){
						$folder = $file;
						$zip->addEmptyDir(ltrim($folder, '/'));
						
						Folder::fix_path($folder);
						$contents = Folder::scan($folder);
						
						//Adiciona os arquivos da pasta atual
						foreach($contents->files as $folder_file)
							$zip->addFile(ROOT.$folder.$folder_file, ltrim($folder, '/').$folder_file);
						
						//Adiciona as subpastas da pasta atual à lista de arquivos a serem compactados
						foreach($contents->folders as $subfolder)
							$files[] = $folder.$subfolder;
					}
				}
				
				return $zip->close();
			}
			
			return false;
		}
		
		/**
		 * Descompacta um arquivo ZIP.
		 * 
		 * @param string $file Caminho do arquivo .zip a ser descompactado.
		 * @param string $target_folder Pasta de destino dos arquivos descompactados.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function unzip($file, $target_folder){
			$zip = new \ZipArchive();
			
			if($zip->open(ROOT.$file) !== true)
				return false;
			
			Folder::fix_path($target_folder);
			
			if(!Folder::create($target_folder))
				return false;
			
			$result = $zip->extractTo(ROOT.$target_folder);
			$zip->close();
			
			return $result;
		}
	}
?>