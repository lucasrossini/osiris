<?php
	namespace User;
	
	/**
	 * Classe para manipulação de sessões e login de usuários.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 25/03/2014
	*/
	
	class User{
		const SESSION_SUFFIX = '_user_id';
		
		private $id;
		private $table;
		private $history_table;
		private $session;
		private $error;
		private $values;
		protected $is_admin;
		
		/**
		 * Instancia um usuário.
		 * 
		 * @param string $table Nome da tabela do banco de dados onde estão localizados os registros de administradores.
		 * @param string $history_table Nome da tabela do banco de dados que grava o histórico de logins de administradores.
		 * @param string $session Nome da sessão onde deve ser gravada as informações de login.
		 */
		public function __construct($table = 'sys_user', $history_table = 'sys_user_login_history', $session = KEY){
			global $db;
			
			$this->table = $table;
			$this->history_table = $history_table;
			$this->session = $session.self::SESSION_SUFFIX;
			$this->error = '';
			$this->values = array();
			$this->is_admin = (get_called_class() == 'User\Admin');
			$this->id = $this->is_logged() ? (int)\HTTP\Session::get($this->session) : null;
			
			//Efetua login do cookie
			if(\HTTP\Cookie::exists($this->session) && !$this->is_logged()){
				\HTTP\Session::create($this->session, \HTTP\Cookie::get($this->session));
				$this->id = (int)\HTTP\Session::get($this->session);
				$this->log();
			}
			
			if($this->is_logged()){
				if(!empty($this->table)){
					//Desloga o usuário se ele não existir mais
					$db->query('SELECT COUNT(*) AS total FROM '.$this->table.' WHERE id = '.$this->id);
					
					if(!$db->result(0)->total){
						$this->logout('/');
						return;
					}
					
					//Carrega os dados do usuário
					$this->load_user_values();
				}
			}
		}
		
		/**
		 * Carrega os dados do usuário.
		 */
		private function load_user_values(){
			global $db;
			
			if($this->is_logged()){
				$this->values = array();
				
				$db->query('SELECT * FROM '.$this->table.' WHERE id = '.$this->id);
				$user_values = $db->result(0);
				
				if(sizeof($user_values)){
					foreach($user_values as $user_value_key => $user_value_content)
						$this->values[$user_value_key] = $user_value_content;
				}
			}
		}
		
		/**
		 * Verifica se o usuário é administador.
		 * 
		 * @return boolean TRUE caso seja administrador ou FALSE caso não seja administrador.
		 */
		public function is_admin(){
			return $this->is_admin;
		}
		
		/**
		 * Verifica se os dados de login estão corretos.
		 * 
		 * @param array $data Vetor multidimensional com os dados de login que possui as chaves 'login' e 'password', cujo valor de cada uma é um vetor com os índices 'field', que indica o campo da tabela do banco de dados que o representa (ou para a chave 'login', contendo um vetor com os nomes dos campos da tabela do banco de dados que podem ser utilizadas); e 'value', que indica o valor de entrada do dado no formulário de login. Possui também o índice 'extra', que contém um vetor com campos e valores a serem pré-definidos na consulta SQL a ser realizada.
		 * @return int|boolean ID do usuário caso os dados estiverem corretos ou FALSE caso não estiverem corretos.
		 */
		public function check_login($data = array()){
			global $db, $sys_language;
			
			//Login com o Facebook
			if(\HTTP\Request::get(\Social\Facebook\Facebook::LOGGED_PARAM)){
				global $sys_facebook_sdk;
				$db->query('SELECT id FROM '.$this->table.' WHERE '.\Social\Facebook\Facebook::FACEBOOK_ID_FIELD.' = '.$sys_facebook_sdk['user']);
				
				if($db->row_count())
					return $db->result(0)->id;
			}
			
			//Login com usuário e senha
			if(sizeof($data)){
				$where_clause = '';
				
				if(is_array($data['login']['field'])){
					foreach($data['login']['field'] as $db_field)
						$where_clause .= '`'.$db_field.'` = "'.$data['login']['value'].'" OR ';
					
					$where_clause = rtrim($where_clause, ' OR ');
				}
				else{
					$where_clause = '`'.$data['login']['field'].'` = "'.$data['login']['value'].'"';
				}
				
				//Campos extras
				$extra_clause = '';
				
				if(is_array($data['extra'])){
					foreach($data['extra'] as $field => $value)
						$extra_clause .= ' AND `'.$field.'` = "'.$value.'"';
				}
				
				$db->query('SELECT id, `'.$data['password']['field'].'` FROM '.$this->table.' WHERE ('.$where_clause.')'.$extra_clause);
				
				if($db->row_count()){
					$result = $db->result(0);
					
					$db_pass = $result->$data['password']['field'];
					$user_id = $result->id;
					
					if(\Security\Crypt::undo($db_pass) == $data['password']['value'])
						return $user_id;
					else
						$this->error = $sys_language->get('class_user', 'wrong_password');
				}
				else{
					$this->error = $sys_language->get('class_user', 'invalid_login');
				}
			}
			
			return false;
		}
		
		/**
		 * Efetua login.
		 * 
		 * @param array $data Vetor multidimensional com os dados de login que possui as chaves 'login' e 'password', cujo valor de cada uma é um vetor com os índices 'field', que indica o campo da tabela do banco de dados que o representa (sendo 'fields' para a chave 'login', contendo um vetor com os nomes dos campos da tabela do banco de dados que podem ser utilizadas); e 'value', que indica o valor de entrada do dado no formulário de login. Possui também o índice 'extra', que contém um vetor com campos e valores a serem pré-definidos na consulta SQL a ser realizada.
		 * @param string $next_url Endereço da página a ser redirecionada após o login.
		 * @param boolean $show_error Define se a mensagem de erro deve ser exibida caso ocorra falha no login.
		 * @param boolean $remember_data Define se os dados da sessão devem ser lembrados criando cookies.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function login($data = array(), $next_url = '', $show_error = true, $remember_data = false){
			$this->logout();
			
			if($user_id = $this->check_login($data)){
				if($remember_data)
					\HTTP\Cookie::create($this->session, $user_id);
				
				\HTTP\Session::create($this->session, $user_id);
				$this->id = $user_id;
				$this->log();
				
				if($next_url)
					\URL\URL::redirect($next_url);
				
				$this->load_user_values();
				return true;
			}
			
			if($show_error)
				$this->show_error();
			
			return false;
		}
		
		/**
		 * Efetua login de um usuário através de seu ID.
		 * 
		 * @param int $id ID do usuário.
		 * @param boolean $remember_data Define se os dados da sessão devem ser lembrados criando cookies.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function force_login($id, $remember_data = false){
			global $db;
			$db->query('SELECT COUNT(*) AS total FROM '.$this->table.' WHERE id = '.$id);
			
			if($db->result(0)->total){
				$this->logout();
				
				if($remember_data)
					\HTTP\Cookie::create($this->session, $id);

				\HTTP\Session::create($this->session, $id);
				
				$this->id = $id;
				$this->log();
				$this->load_user_values();
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Efetua logout.
		 * 
		 * @param string $next_url Endereço da página a ser redirecionada após o logout.
		 */
		public function logout($next_url = ''){
			\HTTP\Session::delete($this->session);
			
			if($this->is_admin())
				\HTTP\Session::delete('ckfinder_admin');
			
			\HTTP\Cookie::delete($this->session);
			
			//Faz logout do Facebook
			if(!\HTTP\Request::get(\Social\Facebook\Facebook::LOGGED_PARAM))
				\Social\Facebook\Facebook::logout();
			
			if($next_url)
				\URL\URL::redirect($next_url);
		}
		
		/**
		 * Verifica se o usuário está logado.
		 * 
		 * @return boolean TRUE caso esteja logado ou FALSE caso não esteja logado.
		 */
		public function is_logged(){
			return \HTTP\Session::exists($this->session);
		}
		
		/**
		 * Insere um registro na tabela de histórico de login.
		 * 
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		private function log(){
			global $db;
			
			if(!empty($this->history_table) && $this->is_logged()){
				$user_id_field = $this->is_admin() ? 'admin_id' : 'user_id';
				return (boolean)$db->query('INSERT INTO '.$this->history_table.' ('.$user_id_field.', date, time) VALUES ('.$this->id.', CURDATE(), CURTIME())');
			}
			
			return false;
		}
		
		/**
		 * Exibe o erro de login.
		 * 
		 * @param string $class Classe CSS atribuída à caixa que contém a mensagem de erro.
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public function show_error($class = 'form-box error', $echo = true){
			$html = '';
			
			if($this->error)
				$html = '<div class="'.$class.'">'.$this->error.'</div>';
			
			if($echo)
				echo $html;
			else
				return $html;
		}
		
		/**
		 * Carrega o erro.
		 * 
		 * @return string Mensagem de erro.
		 */
		public function get_error(){
			return $this->error;
		}
		
		/**
		 * Retorna um dado do usuário logado.
		 * 
		 * @param string $field Nome do campo na tabela do banco de dados que contém o valor desejado.
		 * @return string Valor do campo.
		 */
		public function get($field){
			if($this->is_logged() && array_key_exists($field, $this->values))
				return $this->values[$field];
			
			return null;
		}
		
		/**
		 * Carrega a permissão do administrador na ação atual do formulário.
		 * 
		 * @param string $package Slug do pacote.
		 * @param string $module Slug do módulo.
		 * @param string $action Ação do formulário, que pode ser 'insert', 'edit', 'view' ou 'delete'.
		 * @return array Vetor com os índices 'granted', que indica se a ação é permitida; e 'message', que indica a mensagem de erro de permissão caso a ação não seja permitida.
		 */
		public function get_permission($package, $module, $action){
			global $sys_language;
			
			if($this->is_logged() && $this->is_admin()){
				$module_data = $this->get_module_data($package, $module);
				$module_info = $module_data['name'].' / '.\System\System::get_package_info($module_data['section'], $module_data['package'])->name;
				
				switch($action){
					case 'insert':
						$granted = $module_data['can_insert'];
						$error_message = sprintf($sys_language->get('class_user', 'insert_permission'), '<strong>'.$module_info.'</strong>');
						break;
					
					case 'edit':
						$granted = $module_data['can_edit'];
						$error_message = sprintf($sys_language->get('class_user', 'edit_permission'), '<strong>'.$module_info.'</strong>');
						break;
					
					case 'view':
						$granted = $module_data['can_view'];
						$error_message = sprintf($sys_language->get('class_user', 'view_permission'), '<strong>'.$module_info.'</strong>');
						break;
					
					case 'delete':
						$granted = $module_data['can_delete'];
						$error_message = sprintf($sys_language->get('class_user', 'delete_permission'), '<strong>'.$module_info.'</strong>');
						break;
					
					default:
						$granted = true;
						$error_message = '';
						break;
				}
				
				$access = array('granted' => (boolean)$granted, 'message' => $error_message);
			}
			else{
				$access = array('granted' => true, 'message' => '');
			}
			
			return $access;
		}
	}
?>