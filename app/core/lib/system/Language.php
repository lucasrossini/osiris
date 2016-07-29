<?php
	namespace System;
	
	/**
	 * Classe para multilinguagem.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 08/04/2014
	*/
	
	class Language{
		private static $default_path = '/app/lang/';
		private static $cookie_name = 'lang';
		
		public static $default_lang = 'pt-br';
		
		private $path;
		private $current;
		private $ini;
		
		/**
		 * Instancia um objeto de manipulação de idioma do sistema.
		 * 
		 * @param string $lang Idioma a ser utilizado.
		 * @param string $path Pasta onde está localizada o arquivo INI com a configuração do idioma.
		 */
		public function __construct($lang = '', $path = ''){
			$this->path = !empty($path) ? $path : self::$default_path;
			\Storage\Folder::fix_path($this->path);
			
			$this->set($lang);
		}
		
		/**
		 * Define o idioma a ser utilizado no sistema.
		 * 
		 * @param string $lang Idioma a ser utilizado.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function set($lang = ''){
			global $sys_config;
			
			//Já está definida no cookie
			if(empty($lang) && \HTTP\Cookie::exists(self::$cookie_name)){
				$lang = \HTTP\Cookie::get(self::$cookie_name);
			}
			else{
				if(!array_key_exists($lang, self::get_available_languages()))
					$lang = ($sys_config instanceof \System\Config) ? $sys_config->get('language') : self::$default_lang;

				if(!\Storage\File::exists($this->path.$lang.'.ini'))
					$lang = self::$default_lang;
			}
			
			//Arquivo não existe
			if(!\Storage\File::exists($this->path.$lang.'.ini'))
				return false;
			
			//Define valores
			$this->ini = parse_ini_file(ROOT.$this->path.$lang.'.ini', true);
			$this->current = $lang;
			
			//Cria cookie e sessão
			\HTTP\Cookie::create(self::$cookie_name, $lang);
			\HTTP\Session::create(self::$cookie_name, $lang);
			
			return true;
		}
		
		/**
		 * Carrega o valor de uma entrada do idioma atual.
		 * 
		 * @param string $section Seção onde está localizada a entrada.
		 * @param string $entry Nome da entrada.
		 * @return string|array Valor da entrada ou vetor com as entradas da seção caso o nome da entrada não seja definido.
		 */
		public function get($section = '', $entry = ''){
			$entry = trim($entry);
			
			if(!empty($section)){
				$value = !empty($entry) ? $this->ini[$section][$entry] : $this->ini[$section];
				
				//Verifica no idioma padrão se estiver vazio
				if(empty($value) || !sizeof($value)){
					if(\Storage\File::exists($this->path.self::$default_lang.'.ini')){
						$ini = parse_ini_file(ROOT.$this->path.self::$default_lang.'.ini', true);
						$value = !empty($entry) ? $ini[$section][$entry] : $ini[$section];
					}
				}
			}
			else{
				$value = $this->ini;
			}
			
			return $value;
		}
		
		/**
		 * Monta o script que possui todas as entradas do idioma atual.
		 * 
		 * @return string Script do idioma.
		 */
		public function get_script(){
			return '
				<script src="app/assets/js/language.js"></script>
				<script>Language.entries = $.parseJSON("'.addslashes(json_encode($this->get())).'");</script>
			';
		}
		
		/**
		 * Retorna o idioma atual.
		 * 
		 * @param boolean $capitalize_suffix Define se o sufixo de desambiguação da linguagem deve ser retornado em letras maiúsculas.
		 * @return string Idioma atual.
		 */
		public static function get_current_lang($capitalize_suffix = false){
			$lang = \HTTP\Session::exists(self::$cookie_name) ? \HTTP\Session::get(self::$cookie_name) : self::$default_lang;
			$lang_pieces = explode('-', $lang);
			
			return ($capitalize_suffix && (sizeof($lang_pieces) === 2)) ? $lang_pieces[0].'-'.strtoupper($lang_pieces[1]) : $lang;
		}
		
		/**
		 * Retorna os idiomas disponíveis.
		 * 
		 * @return array Vetor com os idiomas disponíveis, onde a chave é a sigla do idioma e o valor é o nome do idioma.
		 */
		public static function get_available_languages(){
			$available = array();
			$folder_content = \Storage\Folder::scan(self::$default_path);
			
			foreach($folder_content->files as $file){
				$lang = \Storage\File::name($file);
				$ini = parse_ini_file(ROOT.self::$default_path.$lang.'.ini', true);
				$available[$lang] = $ini['general']['name'];
			}
			
			asort($available);
			return $available;
		}
		
		/**
		 * Reinicia a linguagem do sistema.
		 * 
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function reset(){
			return \HTTP\Cookie::delete(self::$cookie_name);
		}
	}
?>