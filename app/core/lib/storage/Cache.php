<?php
	namespace Storage;
	
	/**
	 * Classe para manipulação de cache.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 25/03/2013
	 */
	
	class Cache{
		const DIR = '/app/cache/';
		
		private $time;
		private $key;
		private $tmp_file;
		
		/**
		 * Cria um objeto de cache.
		 * 
		 * @param string $key Nome único para identificar o arquivo de cache.
		 * @param string $extension Extensão a ser atribuída ao arquivo de cache.
		 * @param int $time Tempo (em minutos) de expiração do arquivo de cache.
		 */
		public function __construct($key, $extension = 'tmp', $time = 5){
			$this->key = $key;
			$this->time = (int)$time.' minutes';
			$this->tmp_file = sha1($this->key).'.'.$extension;
		}
		
		/**
		 * Lê o cache.
		 * 
		 * @return string|boolean Conteúdo do arquivo de cache caso ele exista ou FALSE caso ele não exista.
		 */
		public function read(){
			if(File::exists(self::DIR.$this->tmp_file)){
				$content = unserialize(File::load(self::DIR, $this->tmp_file));
				
				if($content['expires'] > time())
					return $content['content'];
				else
					$this->delete();
			}
			
			return false;
		}
		
		/**
		 * Grava um conteúdo no arquivo de cache.
		 * 
		 * @param string $content Conteúdo a ser gravado.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function save($content){
			$content = serialize(array('expires' => strtotime($this->time), 'content' => $content));
			return File::put(self::DIR, $this->tmp_file, $content);
		}
		
		/**
		 * Apaga um arquivo de cache.
		 * 
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function delete(){
			return File::delete(self::DIR, $this->tmp_file);
		}
		
		/**
		 * Retorna o caminho do arquivo de cache.
		 * 
		 * @return string Caminho do arquivo de cache.
		 */
		public function get_file(){
			return self::DIR.$this->tmp_file;
		}
	}
?>