<?php
	namespace User;
	
	/**
	 * Classe para manipulação de sessões, login e controle de acesso de administradores do sistema.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 30/08/2013
	*/
	
	class Admin extends User{
		const MASTER = 1;
		
		/**
		 * Instancia um novo administrador do sistema.
		 * 
		 * @param string $db_table Nome da tabela do banco de dados onde estão localizados os registros de administradores.
		 * @param string $db_history_table Nome da tabela do banco de dados que grava o histórico de logins de administradores.
		 * @param string $session Nome da sessão onde deve ser gravada as informações de login.
		 */
		public function __construct($db_table = 'sys_admin', $db_history_table = 'sys_admin_login_history', $session = ''){
			$session = empty($session) ? KEY.'_adm' : $session;
			
			parent::__construct($db_table, $db_history_table, $session);
			$this->set_ckfinder_permission();
		}
		
		/**
		 * Verifica se o administrador é o principal do sistema.
		 * 
		 * @return boolean TRUE caso seja o principal ou FALSE caso contrário.
		 */
		public function is_master(){
			return ((int)$this->get('level_id') === self::MASTER);
		}
		
		/**
		 * Carrega os módulos do sistema cujo administrador possui acesso.
		 * 
		 * @return array Vetor no formato $result['slug da seção']['slug do pacote']['slug do módulo'] com os índices 'name', que indica o nome do módulo; 'description', que indica a descrição do módulo; 'package', que indica o slug do pacote onde o módulo está localizado; e 'can_insert', 'can_edit', 'can_delete' e 'can_view', que indicam TRUE se o administrador possuir permissão e FALSE se não possuir, respectivamente, para inserir, editar, apagar e visualizar registros nesse módulo.
		 */
		public function get_modules(){
			global $db;
			$result = array();
			
			if($this->is_logged()){
				$db->query('SELECT m.section, m.slug, m.package, l.can_insert, l.can_edit, l.can_delete, l.can_view FROM sys_module m, sys_module_access_level l WHERE l.module_id = m.id AND l.level_id = '.$this->get('level_id').' AND m.active = 1 AND (l.can_insert = 1 OR l.can_edit = 1 OR l.can_delete = 1 OR l.can_view = 1) ORDER BY m.section DESC, m.package');
				$modules = $db->result();
				$current_package = '';
				$i = 0;
				
				foreach($modules as $module){
					$module_language = new \System\Language('', '/admin/modules/'.$module->section.'/'.$module->package.'/'.$module->slug.'/lang');					
					
					$result[$module->section][$module->package][$module->slug] = array('name' => $module_language->get('about', 'name'), 'description' => $module_language->get('about', 'description'), 'package' => $module->package, 'package_name' => \System\System::get_package_info($module->section, $module->package)->name, 'can_insert' => $module->can_insert, 'can_edit' => $module->can_edit, 'can_delete' => $module->can_delete, 'can_view' => $module->can_view);
					$current_package = $module->package;
					
					//Ordena os módulos alfabeticamente
					if($current_package != $modules[++$i]->package)
						ksort($result[$module->section][$module->package]);
				}
			}
			
			return $result;
		}
		
		/**
		 * Carrega os dados de um módulo cujo administrador possui acesso.
		 * 
		 * @param string $package Slug do pacote que contém o módulo. 
		 * @param string $slug Slug do módulo.
		 * @return array Vetor com os índices 'name', que indica o nome do módulo; 'section', que indica o slug da seção onde o pacote do módulo está localizado; 'package', que indica o slug do pacote onde o módulo está localizado; 'description', que indica a descrição do módulo; e 'can_insert', 'can_edit', 'can_delete' e 'can_view', que indicam TRUE se o administrador possuir permissão e FALSE se não possuir, respectivamente, para inserir, editar, apagar e visualizar registros nesse módulo.
		 */
		public function get_module_data($package, $slug){
			global $db;
			
			$db->query('SELECT m.section, m.slug, m.package, l.can_insert, l.can_edit, l.can_delete, l.can_view FROM sys_module m, sys_module_access_level l WHERE l.module_id = m.id AND l.level_id = '.$this->get('level_id').' AND m.package = "'.$package.'" AND m.slug = "'.$slug.'" AND m.active = 1');
			$module = $db->result(0);
			
			$module_language = new \System\Language('', '/admin/modules/'.$module->section.'/'.$module->package.'/'.$module->slug.'/lang');
			$result = array('name' => $module_language->get('about', 'name'), 'section' => $module->section, 'package' => $module->package, 'description' => $module_language->get('about', 'description'), 'can_insert' => $module->can_insert, 'can_edit' => $module->can_edit, 'can_delete' => $module->can_delete, 'can_view' => $module->can_view);
			
			return $result;
		}
		
		/**
		 * Carrega os relatórios do sistema cujo administrador possui acesso.
		 * 
		 * @return array Vetor no formato $result['slug da seção']['slug do pacote']['slug do módulo'] com os índices 'name', que indica o nome do relatório; 'description', que indica a descrição do relatório; e 'package', que indica o slug do pacote onde o relatório está localizado.
		 */
		public function get_reports(){
			global $db;
			$result = array();
			
			if($this->is_logged()){
				$db->query('SELECT r.name, r.description, r.section, r.slug, r.package FROM sys_report r, sys_report_access_level l WHERE l.report_id = r.id AND l.level_id = '.$this->get('level_id').' AND r.active = 1 ORDER BY r.section, r.package, r.name');
				$reports = $db->result();
				
				foreach($reports as $report)
					$result[$report->section][$report->package][$report->slug] = array('name' => $report->name, 'description' => $report->description, 'package' => $report->package);
			}
			
			return $result;
		}
		
		/**
		 * Carrega os dados de um relatório cujo administrador possui acesso.
		 * 
		 * @param string $package Slug do pacote que contém o relatório. 
		 * @param string $slug Slug do relatório.
		 * @return array Vetor com os índices 'name', que indica o nome do relatório; 'section', que indica o slug da seção onde o pacote do relatório está localizado; 'package', que indica o slug do pacote onde o relatório está localizado; e 'description', que indica a descrição do relatório.
		 */
		public function get_report_data($package, $slug){
			global $db;
			
			$db->query('SELECT r.name, r.description, r.section, r.slug FROM sys_report r, sys_report_access_level l WHERE l.report_id = r.id AND l.level_id = '.$this->get('level_id').' AND r.package = "'.$package.'" AND r.slug = "'.$slug.'" AND r.active = 1');
			$report = $db->result(0);
			
			$result = array('name' => $report->name, 'section' => $report->section, 'package' => $report->package, 'description' => $report->description);
			return $result;
		}
		
		/**
		 * Define permissão para upload de arquivos no editor WYSIWYG.
		 * 
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		private function set_ckfinder_permission(){
			return $this->is_logged() ? \HTTP\Session::create('ckfinder_admin', true) : \HTTP\Session::delete('ckfinder_admin');
		}
	}
?>