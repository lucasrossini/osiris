<?php
	namespace System;
	
	/**
	 * Classe que realiza o controle de URLs do sistema.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 08/04/2014
	*/
	
	class Control{
		const URL_404 = '/404';
		const ADMIN_DIR = 'admin';
		
		private $dir_level;
		private $pages;
		private $title_separator;
		private $page_title_pieces = array();
		private $page_level = 0;
		private $is_home;
		private $in_admin;
		private $not_found;
		private $url_site;
		private $url_array = array();
		private $current_file;
		
		private $allowed_pages = array('/app/core/util/download', '/app/core/util/facebook-like', '/app/core/util/thumb', '/app/core/util/zip', '/app/core/util/modal/wrapper', '/app/core/util/ajax/handler', '/app/core/lib/social/facebook/sdk/src/channel');
		private $allowed_dirs = array('/rss', '/api');
		private $disabled_pages = array('/admin/assets', '/admin/conf', '/admin/images', '/admin/inc', '/admin/modules', '/admin/pages', '/app', '/class', '/site', '/subdomains', '/temp', '/uploads');
		
		/**
		 * Instancia um objeto de controle de URLs do sistema.
		 * 
		 * @param int $dir_level Nível de diretório da página a partir da base.
		 * @param array $pages Vetor de páginas.
		 */
		public function __construct($dir_level = 0, $pages = array()){
			global $sys_config;
			
			$this->dir_level = $dir_level;
			$this->pages = $pages;
			$this->title_separator = ' '.$sys_config->get('title_separator').' ';
			$this->url_site = $_SERVER['REQUEST_URI'];
			
			//Particiona a URL
			$url_request = explode('?', $_SERVER['REQUEST_URI']);
			$url_routes = \Util\ArrayUtil::remove('', explode('/', strip_tags($url_request[0])));
			
			//Captura parâmetros GET
			$get_params = explode('&', $url_request[1]);
			
			if(sizeof($get_params)){
				foreach($get_params as $get){
					$get_pieces = explode('=', $get);
					\HTTP\Request::set('get', $get_pieces[0], $get_pieces[1]);
				}
			}
			
			//Desabilita acesso direto aos arquivos
			$file = is_file($url_routes[$this->dir_level + 1]) ? $url_routes[$this->dir_level + 1] : $url_routes[$this->dir_level + 1].'.php';
			
			if(\Storage\File::exists($file))
				$this->not_found = true;
			
			$this->url_array = $url_routes;
			
			//Verifica se está na área administrativa
			$this->in_admin = ($this->dir_level && ($this->url_array[$this->dir_level - 1] == self::ADMIN_DIR));
			
			//Remove os pedaços pertencentes à base
			for($i = 1; $i <= $dir_level; $i++)
				array_shift($this->url_array);
		}
		
		/**
		 * Processa o conteúdo de acordo com a URL atual.
		 */
		public function process_content(){
			global $db, $sys_control, $sys_user, $sys_config, $sys_assets, $sys_facebook_sdk, $sys_language;
			
			$this->is_home = true;
			$current_area = $this->get_page(0);
			$current_page = $this->get_url();
			
			//Bloqueia acesso à área administrativa
			if(!$sys_user->is_logged() && $this->in_admin && !in_array($current_area, array('login', 'logout'))){
				if(!empty($current_area)){
					\UI\Message::error($sys_language->get('class_control', 'admin_login_warning'));
					$next = '?next='.urlencode('/'.ltrim(str_replace(BASE, '', URL), '/'));
				}
				
				\URL\URL::redirect('/admin/login'.$next);
			}
			
			//Processa a página atual
			if(!empty($current_page) && ($current_page != '/')){
				if((!in_array('/'.$current_area, $this->disabled_pages) && !($this->in_admin && in_array('/admin/'.$current_area, $this->disabled_pages))) || in_array(rtrim($current_page, '/'), $this->allowed_pages))
					self::fix_url();
				
				$this->is_home = false;
				
				//Páginas permitidas fora da lista de páginas do site
				$allowed_page = in_array($this->get_url(false), $this->allowed_pages);
				$allowed_dir = in_array('/'.reset(explode('/', ltrim($this->get_url(false), '/'))), $this->allowed_dirs);
				
				$assets_area = in_array(\HTTP\Request::get('sys_area'), array('admin', 'site')) ? \HTTP\Request::get('sys_area') : $this->get_area();
				
				if($allowed_page){
					//Inclui a página
					$this->current_file = (sizeof($this->url_array) === 1) ? ROOT.'/site'.$this->get_url(false).'.php' : ROOT.$this->get_url(false).'.php';
					include $this->current_file;
					
					//Exibe os recursos necessários
					if(\HTTP\Request::get('sys_assets')){
						$sys_assets->display($assets_area, 'js');
						$sys_assets->display($assets_area, 'css');
					}
					
					exit();
				}
				elseif($allowed_dir){
					$dir = reset(explode('/', ltrim($this->get_url(false), '/')));
					
					switch($dir){
						case 'rss':
							//Processa o feed RSS
							$this->current_file = ROOT.'/app/core/rss/control.php';
							include $this->current_file;
							
							break;
						
						case 'api':
							//Processa a chamada da API
							$this->current_file = ROOT.'/app/core/api/control.php';
							include $this->current_file;
							
							break;
						
						default:
							$this->current_file = ROOT.'/site/'.$dir.'.php';
							include $this->current_file;
							
							if(\HTTP\Request::get('sys_assets'))
								$sys_assets->display($assets_area, 'js');
					}
					
					exit();
				}
				elseif($current_page == '/sitemap.xml'){ //Sitemap dinâmico
					$this->current_file = ROOT.'/site/sitemap.php';
					include $this->current_file;
					
					exit();
				}
				elseif($current_page == '/sitemap-news.xml'){ //Sitemap de notícias para o Google News
					$this->current_file = ROOT.'/site/sitemap-news.php';
					include $this->current_file;
					
					exit();
				}
				elseif($page_info = $this->check_url($current_page)){
					//Páginas que necessitam de login
					if(!$sys_user->is_logged() && $page_info['require_login']){
						\UI\Message::error($sys_language->get('class_control', 'page_login_warning'));
						\URL\URL::redirect('/login?next='.urlencode('/'.ltrim(str_replace(BASE, '', URL), '/')));
					}
					
					//Páginas que aceitam acesso direto
					if($page_info['direct']){
						include ROOT.'/'.ltrim($page_info['file'], '/');
						exit();
					}
				}
				else{
					//Página inexistente
					$this->pages[$current_page] = $this->pages[self::URL_404];
					$this->not_found = true;
				}
			}
		}
		
		/**
		 * Exibe o conteúdo processado da página atual.
		 * 
		 * @param boolean $show_titles Define se os títulos das páginas devem ser exibidos.
		 * @param boolean $show_subtitles Define se os subtítulos das páginas devem ser exibidos.
		 * @param boolean $show_messages Define se as mensagens pendentes do sistema devem ser exibidas no início do conteúdo.
		 * @param string $wrap_id ID do elemento HTML que envolve o conteúdo.
		 */
		public function display_content($show_titles = true, $show_subtitles = true, $show_messages = true, $wrap_id = ''){
			global $db, $sys_control, $sys_user, $sys_config, $sys_assets, $sys_facebook_sdk, $sys_language;
			
			if($this->not_found){
				header('HTTP/1.1 404 Not Found');
				$current_page = self::URL_404;
			}
			else{
				$current_page = $this->is_home ? '/' : $this->get_url();
			}
			
			$this->current_file = $this->pages[$current_page]['file'];
			$titles_html = '';
			
			//Títulos e subtítulo
			if($show_titles && $this->pages[$current_page]['show_title']){
				$titles_html = '
					<header>
						<hgroup>
							<h1>'.$this->pages[$current_page]['title'].'</h1>
							'.(($show_subtitles && $this->pages[$current_page]['show_title'] && !empty($this->pages[$current_page]['subtitle'])) ? '<h2>'.$this->pages[$current_page]['subtitle'].'</h2>' : '').'
						</hgroup>
					</header>
				';
			}
			
			//Mensagens pendentes do sistema
			$messages_html = $show_messages ? '<div id="global-message">'.\UI\Message::show_messages(true, false).'</div>' : '';
			
			echo '
				<div id="ajax-loader-container">
					<span id="ajax-loader" style="display: none">'.$sys_language->get('common', 'loading').'...</span>
					<span id="ajax-result" style="display: none"></span>
				</div>
				
				<section id="'.$wrap_id.'">
					'.$titles_html.'
					'.$messages_html.'
					'.\Util\Tools::get_include_content($this->current_file).'
				</section>
			';
		}
		
		/**
		 * Adiciona páginas permitidas para acesso direto.
		 * 
		 * @param array $pages Vetor com as páginas permitidas.
		 */
		public function add_allowed_pages($pages = array()){
			if(is_array($pages))
				$this->allowed_pages = array_merge($this->allowed_pages, $pages);
		}
		
		/**
		 * Adiciona páginas desabilitadas para acesso direto.
		 * 
		 * @param array $pages Vetor com as páginas bloqueadas.
		 */
		public function add_disabled_pages($pages = array()){
			if(is_array($pages))
				$this->disabled_pages = array_merge($this->disabled_pages, $pages);
		}
		
		/**
		 * Verifica se a URL é válida.
		 * 
		 * @param string $url URL a ser verificada.
		 * @param boolean $add Indica se a página deve ser adicionada ao vetor de páginas caso ela seja válida.
		 * @return boolean|array Vetor de atributos da página em caso de sucesso ou FALSE em caso de invalidade.
		 */
		private function check_url($url, $add = true){
			$page_info = false;
			
			if(array_key_exists($url, $this->pages)){
				if(\Storage\File::exists($this->pages[$url]['file']))
					$page_info = $this->pages[$url];
			}
			elseif(!$this->in_admin){
				//Carrega as classes filhas de "DatabaseObject" que possuem o método "check_url"
				$site_class_folder = \Storage\Folder::scan('/class/dao/');
				$dao_classes = array();
				
				foreach($site_class_folder->files as $class_file)
					$dao_classes['default'][] = \Storage\File::name($class_file);
				
				//Classes E-Commerce
				if(ECOMMERCE){
					$ecommerce_class_folder = \Storage\Folder::scan('/class/dao/ecommerce/');

					foreach($ecommerce_class_folder->files as $class_file)
						$dao_classes['ecommerce'][] = \Storage\File::name($class_file);
				}
				
				$childrens = array();
				$parent = new \ReflectionClass('\Database\DatabaseObject');
				
				foreach($dao_classes as $area => $area_classes){
					foreach($area_classes as $dao_class){
						$class_fullname = ($area == 'ecommerce') ? '\DAO\Ecommerce\\'.$dao_class : '\DAO\\'.$dao_class;
						$reflection_class = new \ReflectionClass($class_fullname);

						if($reflection_class->isSubclassOf($parent) && $reflection_class->hasMethod('check_url') && $reflection_class->hasConstant('BASE_PATH') && $reflection_class->hasConstant('PATH_SIZE'))
							$childrens[] = array('name' => $reflection_class->getName(), 'fullname' => $class_fullname, 'base_path' => $reflection_class->getConstant('BASE_PATH'), 'path_size' => $reflection_class->getConstant('PATH_SIZE'), 'search_path' => $reflection_class->getConstant('SEARCH_PATH'), 'search_area' => $reflection_class->getConstant('SEARCH_AREA'));
					}
				}
				
				//Verifica se a URL é válida de acordo com as classes
				$current_path = '/';
				$url_size = (sizeof(explode('/', $url)) - 1);
				$url_array = explode('/', ltrim($url, '/'));
				
				if((sizeof($url_array) - 1)){
					$url_array_size = sizeof($url_array);
					
					for($i = 0; $i < $url_array_size - 1; $i++)
						$current_path .= $url_array[$i].'/';
				}
				
				foreach($childrens as $children){
					$current_class = $children['name'];
					$current_class_fullname = $children['fullname'];
					
					if((strpos($current_path, $children['base_path']) !== false) && ($url_size === $children['path_size'])){
						if($page_info = $children['name']::check_url($url))
							break;
					}
					elseif($url === $children['search_path']){
						$page_info = array('title' => $children['search_area'], 'subtitle' => '', 'file' => 'search.php', 'search_class' => $children['name'], 'canonical' => true);
						break;
					}
				}
			}
			
			if($page_info && !$page_info['class_name'])
				$page_info['class_name'] = $current_class_fullname;
			
			if($add)
				$this->pages[$url] = $page_info;
			
			return $page_info;
		}
		
		/**
		 * Verifica se o RSS é válido.
		 * 
		 * @param string $url URL a ser verificada.
		 * @return boolean|array Vetor com os itens do RSS da página ou FALSE em caso de invalidade.
		 */
		public function check_rss($url){
			//Carrega as classes filhas de "DatabaseObject" que possuem o atributo "$rss_data" ou possuem o método "get_rss"
			$site_class_folder = \Storage\Folder::scan('/class/dao/');
			$site_classes = array();
			
			foreach($site_class_folder->files as $class_file)
				$site_classes[] = \Storage\File::name($class_file);
			
			$childrens = array();
			$parent = new \ReflectionClass('\Database\DatabaseObject');
			
			foreach($site_classes as $site_class){
				$site_class = '\DAO\\'.$site_class;
				$reflection_class = new \ReflectionClass($site_class);
				
				if($reflection_class->isSubclassOf($parent) && ($reflection_class->hasProperty('rss_data') || ($reflection_class->getMethod('get_rss')->class == $site_class)))
					$childrens[] = array('name' => $reflection_class->getName());
			}
			
			//Verifica se o RSS é válido de acordo com as classes
			$url_pieces = explode('/', trim($url, '/'));
			
			if(\Storage\File::extension($url_pieces[sizeof($url_pieces) - 1]) == 'xml'){
				$url = str_replace('.xml', '', implode('/', array_slice($url_pieces, 1, (sizeof($url_pieces) - 1))));
				
				foreach($childrens as $children){
					if($record_id = $children['name']::check_rss($url))
						return $children['name']::get_rss($record_id);
				}
			}
			
			return false;
		}
		
		/**
		 * Faz uma chamada à API.
		 * 
		 * @param string $url URL a ser verificada.
		 * @param string $type Tipo de retorno, que pode ser 'json' ou 'xml'.
		 */
		public function api_call($url, $type){
			$url_pieces = explode('/', trim($url, '/'));
			
			if($url_pieces[0] != 'api')
				return false;
			
			//Carrega as classes filhas de "DatabaseObject" que possuem o atributo "api_data"
			$site_class_folder = \Storage\Folder::scan('/class/dao/');
			$site_classes = array();
			
			foreach($site_class_folder->files as $class_file)
				$site_classes[] = \Storage\File::name($class_file);
			
			$parent = new \ReflectionClass('\Database\DatabaseObject');

			foreach($site_classes as $site_class){
				$site_class = '\DAO\\'.$site_class;
				$reflection_class = new \ReflectionClass($site_class);
				
				$api_name = $url_pieces[1];
				$api_method = $url_pieces[2];
				
				if($reflection_class->isSubclassOf($parent) && $reflection_class->hasProperty('api_data') && ($site_class::$api_data['name'] == $api_name))
					$site_class::call($api_method, $type);
			}
			
			\System\API::throw_error(\System\API::ERROR_INVALID_CALL, $type);
		}
		
		/**
		 * Retorna uma página do vetor de páginas.
		 * 
		 * @param int $page_level Nível da página na URL atual.
		 * @return string|boolean Slug da página em caso de sucesso ou FALSE em caso de falha.
		 */
		public function get_page($page_level){
			return $this->url_array[$page_level] ? $this->url_array[$page_level] : false;
		}
		
		/**
		 * Carrega um atributo da página.
		 * 
		 * @param int|string $page Nível ou slug da página.
		 * @param string $attr Atributo a ser carregado.
		 * @return string|boolean Valor do atributo caso ele exista ou FALSE caso ele não exista.
		 */
		public function get_page_attr($page, $attr){
			if(is_int($page)){
				$url = '';
				
				for($i = 0; $i <= $page; $i++)
					$url .= '/'.$this->get_page($i);
				
				$page = $url;
			}
			
			if(!array_key_exists($page, $this->pages))
				$this->check_url($page);

			if(array_key_exists($page, $this->pages))
				return ($attr == 'url') ? $page : $this->pages[$page][$attr];
			
			return false;
		}
		
		/**
		 * Define um atributo da página.
		 * 
		 * @param int|string $page Nível ou slug da página.
		 * @param string $attr Atributo a ser definido.
		 * @param string $value Valor do atributo.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function set_page_attr($page, $attr, $value){
			if(is_int($page)){
				$url = '';
				
				for($i = 0; $i <= $page; $i++)
					$url .= '/'.$this->get_page($i);
				
				$page = $url;
			}

			if(array_key_exists($page, $this->pages)){
				$this->pages[$page][$attr] = $value;
				return true;
			}
			
			return false;
		}
		
		/**
		 * Carrega um atributo da página atual.
		 * 
		 * @param string $attr Atributo a ser carregado.
		 * @return string Valor do atributo.
		 */
		public function get_current_page_attr($attr){
			return $this->get_page_attr((sizeof($this->url_array) - 1), $attr);
		}
		
		/**
		 * Define o valor de um atributo da página atual.
		 * 
		 * @param string $attr Atributo a ser definido.
		 * @param string $value Valor do atributo.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function set_current_page_attr($attr, $value){
			return $this->set_page_attr((sizeof($this->url_array) - 1), $attr, $value);
		}
		
		/**
		 * Retorna em qual área se encontra a página atual.
		 * 
		 * @return string Área da página atual ('admin' ou 'site').
		 */
		public function get_area(){
			return $this->in_admin ? 'admin' : 'site';
		}
		
		/**
		 * Retorna o título do site na página atual.
		 * 
		 * @return string Título da página.
		 */
		public function get_title(){
			$title = '';
			
			if($this->not_found){
				$this->page_title_pieces[] = $this->pages[self::URL_404]['title'];
			}
			elseif(!$this->is_home){
				$inc_url = '';

				for($i = 0; $i < sizeof($this->url_array); $i++){
					$inc_url .= '/'.$this->url_array[$i];

					if($page_info = $this->check_url($inc_url, false))
						$this->page_title_pieces[] = $page_info['title'];
				}
			}

			for($t = sizeof($this->page_title_pieces) - 1; $t >= 0; $t--)
				$title .= !$t ? $this->page_title_pieces[$t] : $this->page_title_pieces[$t].$this->title_separator;

			if(!empty($title))
				$title .= ' &ndash; ';

			$subtitle = defined('SUBTITLE') ? ' - '.SUBTITLE : '';
			$title = !$this->is_home ? $title.TITLE : TITLE.$subtitle;
			
			if($this->get_area() == 'admin'){
				global $sys_language;
				$title .= ' / '.$sys_language->get('admin_footer', 'admin_system');
			}
			
			return $title;
		}
		
		/**
		 * Retorna o vetor de páginas do site.
		 * 
		 * @return array Vetor com as páginas do site.
		 */
		public function get_pages(){
			return $this->pages;
		}
		
		/**
		 * Retorna o nível de diretório do site.
		 * 
		 * @return int Nível de diretório.
		 */
		public function get_dir_level(){
			return $this->dir_level;
		}
		
		/**
		 * Retorna o nível da página atual.
		 * 
		 * @return int Nível de diretório da página atual.
		 */
		public function get_page_level(){
			return $this->page_level;
		}
		
		/**
		 * Retorna o tamanho do caminho da URL atual.
		 * 
		 * @param boolean $force_dir Indica se a contagem deve ser realizada a partir do nível de diretório do site ou a partir da base.
		 * @return int Tamanho da URL atual.
		 */
		public function get_url_size($force_dir = true){
			$base = $force_dir ? $this->dir_level : 0;
			return sizeof($this->url_array) - $base;
		}
		
		/**
		 * Retorna a URL atual.
		 * 
		 * @param boolean $force_dir Indica se a URL deve ser retornada a partir do nível de diretório do site ou a partir da base.
		 * @return string URL atual.
		 */
		public function get_url($force_dir = false){
			$base = $force_dir ? $this->dir_level : 0;
			$url_array_size = sizeof($this->url_array);
			$url = '';
			
			for($i = $base; $i < $url_array_size; $i++)
				$url .= '/'.$this->url_array[$i];
			
			return !empty($url) ? $url : '/';
		}
		
		/**
		 * Verifica se a página atual é a página inicial.
		 * 
		 * @return boolean Indica se é ou não a página inicial.
		 */
		public function is_home(){
			return $this->is_home;
		}
		
		/**
		 * Verifica se a página não foi encontrada (erro 404).
		 * 
		 * @return boolean Indica se a página foi ou não encontrada.
		 */
		public function not_found(){
			return $this->not_found;
		}
		
		/**
		 * Retorna o arquivo atual de código incluso.
		 * 
		 * @return string Nome do arquivo.
		 */
		public function get_current_file(){
			return $this->current_file;
		}
		
		/**
		 * Gera um breadcrumb para a página atual.
		 * 
		 * @param string $separator Separador entre as páginas do breadcrumb.
		 * @param boolean $include_home Indica se a página inicial deve ou não ser incluída no breadcrumb.
		 * @return string HTML do breadcrumb.
		 */
		public function get_breadcrumb($separator = ' &rsaquo; ', $include_home = true){
			global $sys_language;
			$html = '';
			
			if(!$this->is_home){
				$breadcrumb = $include_home ? array('<a href="'.BASE.'" title="'.$sys_language->get('class_control', 'go_to_page').' &quot;'.$this->pages['/']['title'].'&quot;">'.$this->pages['/']['title'].'</a>') : array();
				$inc_url = '';
				$url_array_size = sizeof($this->url_array);

				if($this->not_found){
					$breadcrumb[] = $this->pages[self::URL_404]['title'];
				}
				elseif($url_array_size){
					foreach($this->url_array as $url_piece){
						$inc_url .= '/'.$url_piece;

						if($page_info = $this->check_url($inc_url, false))
							$breadcrumb[] = '<a href="'.ltrim($inc_url, '/').'" title="'.$sys_language->get('class_control', 'go_to_page').' &quot;'.$page_info['title'].'&quot;">'.$page_info['title'].'</a>';
					}
				}

				$html = '
					<div id="breadcrumb">
						<div class="wrapper">'.implode('<span class="sep">'.$separator.'</span>', $breadcrumb).'</div>
					</div>
				';
			}
			
			return $html;
		}
		
		/**
		 * Gera um menu com as páginas do site.
		 * 
		 * @param int $fade_speed Velocidade do efeito de transição ao exibir um submenu.
		 * @return string HTML do menu.
		 */
		public function get_menu($fade_speed = 300){
			$html = '';
			$current_page = '/'.$this->get_page(0);
			$has_submenu = false;
			
			if(sizeof($this->pages)){
				$html .= '
					<nav id="menu">
						<ul>
				';
				
				foreach($this->pages as $page_name => $page_attr){
					if($page_attr['in_menu'] && !$page_attr['submenu_of']){
						$html .= '<li>';
						
						//Marca a página atual
						$current_class = ($page_name == $current_page) ? 'current' : '';
						$submenu_class = $page_attr['has_submenu'] ? 'submenu-link' : '';
						
						$html .= '<a href="'.$page_name.'" class="'.$submenu_class.' '.$current_class.'"><span>'.$page_attr['title'].'</span></a>';
						
						//Monta o submenu do item atual, se existir
						if($page_attr['has_submenu']){
							$has_submenu = true;
							$html .= '<ul class="submenu">';
							
							if($page_attr['in_submenu']){
								$page_title = !empty($page_attr['submenu_title']) ? $page_attr['submenu_title'] : $page_attr['title'];
								$html .= '<li><a href="'.$page_name.'">'.$page_title.'</a></li>';
							}
							
							foreach($this->pages as $subpage_name => $subpage_attr){
								if(($subpage_attr['submenu_of'] == $page_name) && $subpage_attr['in_menu'])
									$html .= '<li><a href="'.$subpage_name.'">'.$subpage_attr['title'].'</a></li>';
							}
							
							$html .= '</ul>';
						}
						
						$html .= '</li>';
					}
				}
				
				$html .= '
						</ul>
					</nav>
				';
				
				//Script do submenu
				if($has_submenu){
					global $sys_assets;
					$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.hoverIntent.min.js');
					
					$html .= '
						<script>
							$(document).ready(function(){
								//Submenu
								var menu_fade_speed = '.(int)$fade_speed.';
								var menu_timer;

								$(".submenu-link").hoverIntent(
									function(){ $(this).addClass("opened").parent().find(".submenu").stop(true, true).fadeIn(menu_fade_speed); },
									function(){ $(this).removeClass("opened").parent().find(".submenu").stop(true, true).fadeOut(menu_fade_speed); }
								);

								$(".submenu").hover(
									function(){
										clearTimeout($(this).parent().find(".submenu-link").prop("hoverIntent_t"));
										$(this).parent().find(".submenu-link").prop("hoverIntent_s", 0);

										$(this).stop(true, true).show().parent().find("a").addClass("opened");
									},
									function(){
										$(this).fadeOut(menu_fade_speed).parent().find("a").removeClass("opened");
									}
								);
							});
						</script>
					';
				}
			}
			
			return $html;
		}
		
		/*-- Tags --*/
		
		/**
		 * Retorna a tag do feed RSS da página atual.
		 * 
		 * @return string HTML da tag.
		 */
		public function get_rss_tag(){
			$tag = '';
			$current_url = $this->get_url();
			$page_attr = $this->pages[$current_url];
			
			if($page_attr['rss']){
				$title = $page_attr['rss_title'] ? $page_attr['rss_title'] : $page_attr['title'];
				$url = $page_attr['rss_page'] ? BASE.'/rss'.$page_attr['rss_page'].'.xml' : BASE.'/rss'.$current_url.'.xml';
				
				$tag = '<link rel="alternate" type="application/rss+xml" title="'.$title.' - '.TITLE.'" href="'.$url.'" />';
			}
			
			return $tag;
		}
		
		/**
		 * Retorna a tag de página canônica para a página atual.
		 * 
		 * @return string HTML da tag.
		 */
		public function get_canonical_tag(){
			$current_page = $this->get_url();
			return ($this->pages[$current_page]['canonical'] || $this->is_home) ? '<link rel="canonical" href="'.BASE.$current_page.'" />' : '';
		}
		
		/**
		 * Retorna a tag de URL base.
		 * 
		 * @return string HTML da tag.
		 */
		public function get_base_tag(){
			return '<base href="'.BASE.'/" />';
		}
		
		/**
		 * Retorna a tag de título da página.
		 * 
		 * @return string HTML da tag.
		 */
		public function get_title_tag(){
			return '<title>'.$this->get_title().'</title>';
		}
		
		/**
		 * Retorna a tag de descrição para a página atual.
		 * 
		 * @return string HTML da tag.
		 */
		public function get_description_tag(){
			$description = '';
			
			if($this->is_home && defined('DESCRIPTION')){
				$description = DESCRIPTION;
			}
			else{
				if($this->get_page_attr($this->get_url(), 'meta_description')){
					$description = $this->get_page_attr($this->get_url(), 'meta_description');
				}
				else{
					$class = $this->get_page_attr($this->get_url(), 'class_name');
					$record_id = $this->get_page_attr($this->get_url(), 'record_id');

					if(!empty($class) && !empty($record_id)){
						$reflection_class = new \ReflectionClass($class);

						if($reflection_class->hasConstant('DESCRIPTION_TAG')){
							$object = new $class($record_id);
							$description = $object->get($reflection_class->getConstant('DESCRIPTION_TAG'));
						}
					}
				}
				
				if(empty($description) && $this->get_page_attr($this->get_url(), 'subtitle'))
					$description = $this->get_page_attr($this->get_url(), 'subtitle');
			}
			
			return !empty($description) ? '<meta name="description" content="'.$description.'" />' : '';
		}
		
		/**
		 * Retorna a tag de palavras-chave do site.
		 * 
		 * @return string HTML da tag.
		 */
		public function get_keywords_tag(){
			$keywords = '';
			
			if($this->is_home && defined('KEYWORDS')){
				$keywords = KEYWORDS;
			}
			else{
				if($this->get_page_attr($this->get_url(), 'meta_keywords')){
					$keywords = $this->get_page_attr($this->get_url(), 'meta_keywords');
				}
				else{
					$class = $this->get_page_attr($this->get_url(), 'class_name');
					$record_id = $this->get_page_attr($this->get_url(), 'record_id');

					if(!empty($class) && !empty($record_id)){
						$reflection_class = new \ReflectionClass($class);

						if($reflection_class->hasConstant('KEYWORDS_TAG')){
							$object = new $class($record_id);
							$keywords = $object->get($reflection_class->getConstant('KEYWORDS_TAG'));
						}
					}
				}
			}
			
			return !empty($keywords) ? '<meta name="keywords" content="'.$keywords.'" />' : '';
		}
		
		/**
		 * Retorna as tags de ícones da página.
		 * 
		 * @return string HTML das tags.
		 */
		public function get_icon_tags(){
			return '
				<link href="admin/images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
				<link href="admin/images/favicon.ico" rel="icon" type="image/ico" />
				<link href="admin/images/apple-touch-icon.png" rel="apple-touch-icon" />
			';
		}
		
		/*-- Métodos estáticos --*/
		
		/**
		 * Retira barra ('/') do final da URL atual.
		 */
		public static function fix_url(){
			if(substr(URL_WITHOUT_GETS, -1) == '/'){
				$url_pieces = explode('?', URL);
				$get_params = (sizeof($url_pieces) > 1) ? '?'.end($url_pieces) : '';
				
				\URL\URL::redirect(substr(URL_WITHOUT_GETS, 0, strlen(URL_WITHOUT_GETS) - 1).$get_params, 301, false);
			}
		}
	}
?>