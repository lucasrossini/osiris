<?php
	namespace Storage;
	
	/**
	 * Classe para download de arquivos.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 14/01/2013
	*/
	
	abstract class Download{
		/**
		 * Efetua o download de um arquivo.
		 * 
		 * @param string $path Pasta onde está localizado o arquivo.
		 * @param string $file Nome do arquivo.
		 * @param boolean $delete_after Indica se o arquivo deve ser apagado imediatamente após o início do download.
		 */
		public static function get($path, $file, $delete_after = false){
			global $sys_language;
			
			Folder::fix_path($path);
			$file = ROOT.$path.utf8_decode($file);
			
			if(!is_file($file))
				die($sys_language->get('class_download', 'not_found'));
		
			$len = filesize($file);
			$filename = basename($file);
			$file_extension = strtolower(substr(strrchr($filename, '.'), 1));
		
			switch($file_extension){
				case 'pdf': $ctype = 'application/pdf'; break;
				case 'exe': $ctype = 'application/octet-stream'; break;
				case 'zip': $ctype = 'application/zip'; break;
				case 'doc': $ctype = 'application/msword'; break;
				case 'xls': $ctype = 'application/vnd.ms-excel'; break;
				case 'ppt': $ctype = 'application/vnd.ms-powerpoint'; break;
				case 'gif': $ctype = 'image/gif'; break;
				case 'png': $ctype = 'image/png'; break;
				case 'jpeg':
				case 'jpg': $ctype = 'image/jpg'; break;
				case 'mp3': $ctype = 'audio/mpeg'; break;
				case 'wav': $ctype = 'audio/x-wav'; break;
				case 'mpeg':
				case 'mpg':
				case 'mpe': $ctype = 'video/mpeg'; break;
				case 'mov': $ctype = 'video/quicktime'; break;
				case 'avi': $ctype = 'video/x-msvideo'; break;
				
				//Extensões que não podem ser baixadas
				case 'php':
				case 'htm':
				case 'html': die(sprintf($sys_language->get('class_download', 'extension_error'), '<strong>'.$file_extension.'</strong>')); break;
				
				default: $ctype = 'application/force-download';
			}
		
			//Define os cabeçalhos
			header('Pragma: public');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: public');
			header('Content-Description: File Transfer');
		   
			//Utiliza o Content-Type gerado
			header('Content-Type: '.$ctype);
		
			//Força o download
			$header = 'Content-Disposition: attachment; filename='.$filename.';';
			header($header);
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: '.$len);
			@readfile($file);
			
			if($delete_after)
				@unlink($file);
			
			exit();
		}
		
		/**
		 * Cria URL de download de um arquivo.
		 * 
		 * @param string $path Pasta onde está localizado o arquivo.
		 * @param string $file Nome do arquivo.
		 * @param boolean $delete_after Indica se o arquivo deve ser apagado imediatamente após o início do download.
		 * @return string URL para download do arquivo.
		 */
		public static function link($path, $file, $delete_after = false){
			$delete_param = $delete_after ? '&d='.\Security\Crypt::exec("1") : '';
			return 'app/core/util/download?p='.\Security\Crypt::exec($path).'&f='.\Security\Crypt::exec($file).$delete_param;
		}
		
		/**
		 * Cria URL de download de vários arquivos compactados em ZIP.
		 * 
		 * @param array $files Vetor com os arquivos/pastas a serem compactados e baixados.
		 * @param boolean $delete_after Indica se o arquivo compactado deve ser apagado imediatamente após o início do download.
		 * @return string URL para download do arquivo.
		 */
		public static function zip_link($files, $delete_after = true){
			$delete_param = $delete_after ? '&d='.\Security\Crypt::exec("1") : '';
			return 'app/core/util/zip?f='.\Security\Crypt::exec(serialize($files)).$delete_param;
		}
	}
?>