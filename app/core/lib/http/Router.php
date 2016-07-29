<?php
	namespace HTTP;
	
	/**
	 * Classe que define o roteamento das páginas do sistema.
	 *
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 17/03/2014
	 */
	
	abstract class Router{
		/**
		 * Carrega a lista de páginas do site.
		 * 
		 * @return array Vetor com a lista de páginas do site.
		 */
		public static function get_site_routes(){
			global $sys_language;
			
			//Páginas padrão
			$site_default_routes = array(
				'/404' => array('title' => $sys_language->get('site_pages', '404'), 'subtitle' => $sys_language->get('site_pages', 'error_404'), 'file' => '/site/pages/404.php', 'show_title' => true, 'sitemap_hidden' => true),
				'/' => array('title' => $sys_language->get('site_pages', 'home'), 'file' => '/site/pages/home.php', 'show_title' => false, 'canonical' => true, 'sitemap_hidden' => true)
			);
			
			//Páginas customizadas
			$site_custom_routes = parse_ini_file(ROOT.'/site/conf/routes.ini', true);
			
			//Páginas E-Commerce
			$ecommerce_routes = ECOMMERCE ? parse_ini_file(ROOT.'/site/ecommerce/conf/routes.ini', true) : array();
			
			//Páginas dinâmicas
			$pages = array();
			$pages_list = \DAO\Page::load_all();
			
			$fixed_routes = array_merge($site_default_routes, $site_custom_routes, $ecommerce_routes);
			
			foreach($pages_list['results'] as $page_obj){
				if($page_obj->get('show')){
					$pages['/'.ltrim($page_obj->get('url'), '/')] = array('title' => $page_obj->get('title'), 'subtitle' => $page_obj->get('subtitle'), 'file' => '/site/pages/db.php', 'show_title' => true, 'class_name' => '\DAO\Page', 'record_id' => $page_obj->get('id'));
					
					if(array_key_exists('/'.$page_obj->get('slug'), $fixed_routes))
						$pages['/'.ltrim($page_obj->get('url'), '/')] = array_merge($pages['/'.ltrim($page_obj->get('url'), '/')], $fixed_routes['/'.$page_obj->get('slug')]);
				}
			}
			
			return array_merge($fixed_routes, $pages);
		}
		
		/**
		 * Carrega a lista de páginas da área administrativa.
		 * 
		 * @return array Vetor com a lista de páginas da área administrativa.
		 */
		public static function get_admin_routes(){
			global $sys_user;
			global $sys_language;
			
			//Páginas padrão
			$admin_default_routes = array(
				'/404' => array('title' => $sys_language->get('admin_pages', '404'), 'subtitle' => $sys_language->get('admin_pages', '404_description'), 'file' => '/admin/pages/404.php', 'icon' => 'admin/images/icons/default/not-found.png', 'module' => '404', 'package' => 'default'),
				'/' => array('title' => $sys_language->get('admin_pages', 'home'), 'subtitle' => $sys_language->get('admin_pages', 'home_description'), 'file' => '/admin/pages/home.php', 'icon' => 'admin/images/icons/default/home.png', 'module' => 'home', 'package' => 'default'),
				'/login' => array('title' => 'Login', 'subtitle' => $sys_language->get('admin_pages', 'login_description'), 'file' => '/admin/pages/login.php', 'icon' => 'admin/images/icons/default/login.png', 'module' => 'login', 'package' => 'default'),
				'/logout' => array('title' => 'Logout', 'subtitle' => '', 'file' => '/admin/pages/logout.php', 'module' => 'logout', 'package' => 'default')
			);
			
			//Páginas customizadas
			$admin_custom_routes = parse_ini_file(ROOT.'/admin/conf/routes.ini', true);
			
			//Módulos permitidos
			$pages = array();
			$modules = $sys_user->get_modules();
			
			foreach($modules as $module_section => $packages_list){
				foreach($packages_list as $package => $modules_list){
					foreach($modules_list as $module => $module_attr){
						$pages['/'.$module_attr['package'].'/'.$module.'/main'] = $pages['/'.$module_attr['package'].'/'.$module.'/list'] = array('title' => $module_attr['name'].' &rsaquo; '.$module_attr['package_name'], 'subtitle' => $module_attr['description'], 'section' => $module_section, 'module' => $module, 'module_name' => $module_attr['name'], 'package' => $module_attr['package'], 'file' => '/admin/inc/module/main.php', 'icon' => 'admin/modules/'.$module_section.'/'.$module_attr['package'].'/'.$module.'/icon-large.png');
						$pages['/'.$module_attr['package'].'/'.$module.'/list']['file'] = '/admin/inc/module/list.php';
					}
				}
			}
			
			return array_merge($admin_default_routes, $admin_custom_routes, $pages);
		}
	}
?>