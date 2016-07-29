<?php
	namespace System;
	
	/**
	 * Classe para controle de módulos e relatórios do sistema.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 14/03/2014
	*/
	
	abstract class System{
		/*-- Controle de pacotes --*/
		
		/**
		 * Captura os dados de um pacote.
		 * 
		 * @param string $section Slug da seção que contém o pacote.
		 * @param string $package Slug do pacote.
		 * @return stdClass|boolean Objeto com os atributos 'slug', que indica o slug do pacote; e 'name', que indica o nome do pacote em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function get_package_info($section, $package){
			if(!empty($section) && !empty($package)){
				if($info = parse_ini_file(ROOT.'/admin/modules/'.$section.'/'.$package.'/lang.ini', true)){
					$package_info = new \stdClass();
					$package_info->slug = $package;
					$package_info->name = \Security\Sanitizer::sanitize(trim(strip_tags($info[\System\Language::get_current_lang()]['name'])));
					
					return $package_info;
				}
			}
			
			return false;
		}
		
		/*-- Controle de módulos --*/
		
		/**
		 * Carrega os pacotes e módulos da seção.
		 * 
		 * @param string $section Slug da seção.
		 * @return array Vetor multidimensional com os índices 'package', que indica o slug do pacote; e 'module', que indica o slug do módulo.
		 */
		public static function load_modules($section){
			global $sys_user;
			$permission = $sys_user->get_permission('settings', 'modules', 'view');
			
			if($permission['granted']){
				$folder_data = \Storage\Folder::scan('/admin/modules/'.$section);
				$modules = array();
				
				foreach($folder_data->folders as $subfolder){
					$package_data = \Storage\Folder::scan('/admin/modules/'.$section.'/'.$subfolder);

					foreach($package_data->folders as $package_subfolder){
						if(self::is_module('/admin/modules/'.$section.'/'.$subfolder.'/'.$package_subfolder)){
							$package_info = self::get_package_info($section, $subfolder);
							$modules[] = array('package' => $package_info->slug, 'module' => $package_subfolder);
						}
					}
				}
				
				//Ordena os módulos
				asort($modules, SORT_REGULAR);
				return $modules;
			}
			
			echo self::permission_error_message($permission['message']);
			return array();
		}
		
		/**
		 * Verifica se uma pasta é um módulo completo.
		 * 
		 * @param string $folder Caminho completo da pasta a ser verificada.
		 * @return boolean TRUE caso a pasta seja um módulo ou FALSE caso a pasta não seja um módulo.
		 */
		private static function is_module($folder){
			$folder_data = \Storage\Folder::scan($folder);
			return (in_array('main.php', $folder_data->files) && in_array('lang', $folder_data->folders));
		}
		
		/**
		 * Carrega as informações de um módulo.
		 * 
		 * @param string $section Slug da seção.
		 * @param string $package Slug do pacote.
		 * @param string $module Slug do módulo.
		 * @return stdClass|boolean Objeto com os atributos 'name', que indica o nome do módulo; e 'description', que indica a descrição do módulo em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function load_module_info($section, $package, $module){
			if(\Storage\File::exists('/admin/modules/'.$section.'/'.$package.'/'.$module.'/lang/'.\System\Language::get_current_lang().'.ini')){
				$module_language = new \System\Language('', '/admin/modules/'.$section.'/'.$package.'/'.$module.'/lang');
				
				$module_info = new \stdClass();
				$module_info->name = \Security\Sanitizer::sanitize(trim(strip_tags($module_language->get('about', 'name'))));
				$module_info->description = \Security\Sanitizer::sanitize(trim(strip_tags($module_language->get('about', 'description'))));
				
				return $module_info;
			}
			
			return false;
		}
		
		/**
		 * Verifica se um módulo existe.
		 * 
		 * @param string $section Slug da seção.
		 * @param string $package Slug do pacote.
		 * @param string $name Slug do módulo.
		 * @return boolean TRUE caso o módulo exista ou FALSE caso o módulo não exista.
		 */
		private static function module_exists($section, $package, $name){
			if(!empty($name)){
				$modules = self::load_modules($section);
				
				foreach($modules as $module){
					if(($module['package'] == $package) && ($module['module'] == $name))
						return true;
				}
			}
			
			return false;
		}
		
		/**
		 * Carrega os dados de um módulo da tabela do banco de dados.
		 * 
		 * @param string $package Slug do pacote.
		 * @param string $module Slug do módulo.
		 * @return array Vetor com o resultado da consulta SQL realizada ao banco de dados.
		 */
		public static function load_module_data($package, $module){
			global $db;
			
			$db->query('SELECT active, section, package FROM sys_module WHERE package = "'.$package.'" AND slug = "'.$module.'"');
			$module_data = $db->result(0);
			
			if(sizeof($module_data)){
				$module_language = new \System\Language('', '/admin/modules/'.$module_data->section.'/'.$package.'/'.$module.'/lang');
				$module_data->name = $module_language->get('about', 'name');
				$module_data->description = $module_language->get('about', 'description');
			}
			
			return $module_data;
		}
		
		/**
		 * Ativa um módulo.
		 * 
		 * @param string $section Slug da seção.
		 * @param string $package Slug do pacote.
		 * @param string $module Slug do módulo.
		 * @param boolean $show_message Define se a mensagem de sucesso/erro será exibida após a ativação do módulo.
		 * @param string $redirect Página a ser redirecionada após a ativação do módulo.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function activate_module($section, $package, $module, $show_message = true, $redirect = URL_WITHOUT_GETS){
			global $db;
			global $sys_user;
			global $sys_language;
			
			$permission = $sys_user->get_permission('settings', 'modules', 'edit');
			
			if($permission['granted']){
				if(self::module_exists($section, $package, $module)){
					$db->query('SELECT COUNT(*) AS total FROM sys_module WHERE section = "'.$section.'" AND package = "'.$package.'" AND slug = "'.$module.'"');
					$sql = $db->result(0)->total ? 'UPDATE sys_module SET active = 1 WHERE section = "'.$section.'" AND package = "'.$package.'" AND slug = "'.$module.'"' : 'INSERT INTO sys_module (active, section, package, slug) VALUES (1, "'.$section.'", "'.$package.'", "'.$module.'")';
					
					if($db->query($sql)){
						$db->query('SELECT id FROM sys_module WHERE section = "'.$section.'" AND package = "'.$package.'" AND slug = "'.$module.'"');
						$module_id = $db->result(0)->id;
						
						//Atribui todas as permissões do módulo ao nível de administrador principal
						$db->query('SELECT id FROM sys_module_access_level WHERE level_id = 1 AND module_id = '.$module_id);
						if($db->row_count()){
							$access_id = $db->result(0)->id;
							$db->query('UPDATE sys_module_access_level SET can_insert = 1, can_edit = 1, can_delete = 1, can_view = 1 WHERE id = '.$access_id);
						}
						else{
							$db->query('INSERT INTO sys_module_access_level (level_id, module_id, can_insert, can_edit, can_delete, can_view) VALUES (1, '.$module_id.', 1, 1, 1, 1)');
						}
						
						if($show_message)
							\UI\Message::success($sys_language->get('class_system', 'activate_module_success'));
						
						if($redirect)
							\URL\URL::redirect($redirect);
						
						return true;
					}
					else{
						if($show_message)
							\UI\Message::error($sys_language->get('class_system', 'activate_module_error'));
						
						if($redirect)
							\URL\URL::redirect($redirect);
					}
				}
				else{
					if($show_message)
						\UI\Message::error($sys_language->get('class_system', 'activate_module_error'));
					
					if(!empty($redirect))
						\URL\URL::redirect($redirect);
				}
			}
			else{
				echo self::permission_error_message($permission['message']);
			}
			
			return false;
		}
		
		/**
		 * Desativa um módulo.
		 * 
		 * @param string $section Slug da seção.
		 * @param string $package Slug do pacote.
		 * @param string $module Slug do módulo.
		 * @param boolean $show_message Define se a mensagem de sucesso/erro será exibida após a desativação do módulo.
		 * @param string $redirect Página a ser redirecionada após a desativação do módulo.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function deactivate_module($section, $package, $module, $show_message = true, $redirect = URL_WITHOUT_GETS){
			global $db;
			global $sys_user;
			global $sys_language;
			
			$permission = $sys_user->get_permission('settings', 'modules', 'edit');
			
			if($permission['granted']){
				if($db->query('UPDATE sys_module SET active = 0 WHERE section = "'.$section.'" AND package = "'.$package.'" AND slug = "'.$module.'"')){
					if($show_message)
						\UI\Message::success($sys_language->get('class_system', 'deactivate_module_success'));
					
					if($redirect)
						\URL\URL::redirect($redirect);
					
					return true;
				}
				else{
					if($show_message)
						\UI\Message::error($sys_language->get('class_system', 'deactivate_module_error'));
					
					if(!empty($redirect))
						\URL\URL::redirect($redirect);
					
					return false;
				}
			}
			
			echo self::permission_error_message($permission['message']);
			return false;
		}
		
		/**
		 * Apaga um módulo.
		 * 
		 * @param string $section Slug da seção.
		 * @param string $package Slug do pacote.
		 * @param string $module Slug do módulo.
		 * @param boolean $show_message Define se a mensagem de sucesso/erro será exibida após a remoção do módulo.
		 * @param string $redirect Página a ser redirecionada após a remoção do módulo.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function delete_module($section, $package, $module, $show_message = true, $redirect = URL_WITHOUT_GETS){
			global $db;
			global $sys_user;
			global $sys_language;
			
			$permission = $sys_user->get_permission('settings', 'modules', 'delete');
			
			if($permission['granted']){
				$db->query('SELECT id FROM sys_module WHERE section = "'.$section.'" AND package = "'.$package.'" AND slug = "'.$module.'"');
				$module_id = $db->result(0)->id;
				
				$success = false;
				
				if($module_id){
					$db->query('DELETE FROM sys_module_access_level WHERE module_id = '.$module_id);
					
					if($db->query('DELETE FROM sys_module WHERE id = '.$module_id))
						$success = true;
				}
				else{
					$success = true;
				}
				
				if($success){
					\Storage\Folder::delete('/admin/modules/'.$section.'/'.$package.'/'.$module);
					$package_content = \Storage\Folder::scan('/admin/modules/'.$section.'/'.$package);
					
					if(!sizeof($package_content->folders))
						\Storage\Folder::delete('/admin/modules/'.$section.'/'.$package);
					
					if($show_message)
						\UI\Message::success($sys_language->get('class_system', 'delete_module_success'));
					
					if($redirect)
						\URL\URL::redirect($redirect);
					
					return true;
				}
				
				if($show_message)
					\UI\Message::error($sys_language->get('class_system', 'delete_module_error'));
				
				if(!empty($redirect))
					\URL\URL::redirect($redirect);
			}
			
			echo self::permission_error_message($permission['message']);
			return false;
		}
		
		/**
		 * Exibe o conteúdo das páginas de um módulo.
		 * 
		 * @param string $package Slug do pacote.
		 * @param string $module Slug do módulo.
		 * @param string $page Página a ser exibida, que pode ser 'form' ou 'list'.
		 */
		public static function include_module($package, $module, $page){
			$html = '';
			$module_data = self::load_module_data($package, $module);
			
			//Carrega as informações do módulo na língua atual
			$module_language = new \System\Language('', '/admin/modules/'.$module_data->section.'/'.$module_data->package.'/'.$module.'/lang/');
			
			switch($page){
				case 'main':
					$html .= \Util\Tools::get_include_content('/admin/modules/'.$module_data->section.'/'.$module_data->package.'/'.$module.'/main.php', array('module_language' => $module_language));
					break;
				
				case 'list':
					if(self::has_list($package, $module))
						$html = \Util\Tools::get_include_content('/admin/modules/'.$module_data->section.'/'.$module_data->package.'/'.$module.'/list.php', array('module_language' => $module_language));
					
					break;
			}
			
			echo $html;
		}
		
		/**
		 * Verifica se um módulo possui página de lista de registros.
		 * 
		 * @param string $package Slug do pacote.
		 * @param string $module Slug do módulo.
		 * @return boolean TRUE caso a página possua lista de registros ou FALSE caso a página não possua lista de registros.
		 */
		public static function has_list($package, $module){
			$module_data = self::load_module_data($package, $module);
			return \Storage\File::exists('/admin/modules/'.$module_data->section.'/'.$module_data->package.'/'.$module.'/list.php');
		}
		
		/**
		 * Monta a mensagem padrão de erro de permissão.
		 * 
		 * @param string $message Mensagem a ser exibida.
		 * @return string HTML montado da mensagem.
		 */
		public static function permission_error_message($message){
			global $sys_language;
			
			$html = '
				<div class="permission-error">
					<p>
						'.$message.'<br />
						'.$sys_language->get('class_system', 'permission_warning').'
					</p>
				</div>
			';
			
			return $html;
		}
	}
?>