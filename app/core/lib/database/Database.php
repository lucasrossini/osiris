<?php
	namespace Database;
	
	/**
	 * Realiza o gerenciamento de banco de dados.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 27/02/2014
	*/
	
	class Database{
		const MAX_EXPORT_RECORDS = 2400;
		
		private $connection;
		private $db_host;
		private $db_user;
		private $db_name;
		
		private $sql;
		private $sql_type;
		private $sql_query;
		
		private $records = array();
		private $fetch_type;
		private $row_count = 0;
		private $queries_count = 0;
		private $queries_history = array();
		
		private $in_transaction = false;
		private $transaction_failed = false;
		private $transaction_queries = array();
		
		/**
		 * Instancia um objeto de manipulação de banco de dados.
		 * 
		 * @param string $db_host Host do banco de dados.
		 * @param string $db_user Usuário do banco de dados.
		 * @param string $db_password Senha do banco de dados.
		 * @param string $db_name Nome do banco de dados.
		 * @param boolean $auto_connect Indica se a conexão deve ser realizada automaticamente.
		 */
		public function __construct($db_host, $db_user, $db_password, $db_name, $auto_connect = true){
			if($auto_connect)
				$this->connect($db_host, $db_user, $db_password, $db_name);
		}
		
		/**
		 * Fecha a conexão com o banco de dados.
		 */
		public function __destruct(){
			mysqli_close($this->connection);
		}
		
		/**
		 * Realiza conexão com um banco de dados.
		 * 
		 * @param string $db_host Host do banco de dados.
		 * @param string $db_user Usuário do banco de dados.
		 * @param string $db_password Senha do banco de dados.
		 * @param string $db_name Nome do banco de dados.
		 * @param boolean $die Indica se a execução deve ser terminada caso ocorra uma falha ao conectar com o banco de dados.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function connect($db_host, $db_user, $db_password, $db_name, $die = true){
			$lang_file = parse_ini_file(ROOT.'/app/lang/'.\System\Language::get_current_lang().'.ini', true);
			
			if(!($this->connection = @mysqli_connect($db_host, $db_user, $db_password))){
				if($die)
					throw new \Exception('<strong>'.$lang_file['common']['error'].':</strong> '.$lang_file['class_database']['connection_error']);
				else
					return false;
			}
			
			$this->db_host = $db_host;
			$this->db_user = $db_user;
			$this->db_name = $db_name;
			
			if(!@mysqli_select_db($this->connection, $this->db_name)){
				if($die)
					throw new \Exception('<strong>'.$lang_file['common']['error'].':</strong> '.$lang_file['class_database']['not_found']);
				else
					return false;
			}
			
			mysqli_set_charset($this->connection, 'utf8');
			return true;
		}
		
		/**
		 * Carrega o link identificador da conexão com o banco de dados.
		 * 
		 * @return int Identificador da conexão com o banco de dados.
		 */
		public function get_connection(){
			return $this->connection;
		}
		
		/**
		 * Realiza uma consulta SQL ao banco de dados.
		 * 
		 * @param string $sql Consulta SQL a ser realizada.
		 * @param string $fetch_type Tipo de resultado de uma consulta do tipo 'SELECT', podendo ser 'object' para um vetor de objetos e 'array' para um vetor multidimensional.
		 * @param string $index_field Indica o nome do campo que deve ter seus valores utilizados como índices do vetor de resultados da consulta.
		 * @param boolean $disable_exception Indica se o lançamento da exceção em caso de erros deve ser desabilitado.
		 * @param boolean $register_time Indica se deve registrar o tempo de execução da consulta.
		 * @throws Exception Erro na consulta.
		 * @return int|boolean ID do registro inserido em consultas 'INSERT', número de linhas afetadas em consultas 'DELETE' ou TRUE em caso de sucesso e FALSE em caso de falha em consultas 'UPDATE' e 'SELECT'.
		 */
		public function query($sql, $fetch_type = 'object', $index_field = '', $disable_exception = false, $register_time = false){
			if(empty($sql))
				return false;
			else
				$sql = trim($sql);
			
			$lang_file = parse_ini_file(ROOT.'/app/lang/'.\System\Language::get_current_lang().'.ini', true);
			
			$this->records = array();
			$this->fetch_type = strtolower($fetch_type);
			$this->sql = $sql;
			$this->sql_type = strtoupper(substr($sql, 0, strpos($sql, ' ')));
			
			//Realiza a consulta
			$msc = microtime(true);
			$this->sql_query = mysqli_query($this->connection, $sql);
			$msc = microtime(true) - $msc;
			
			//Registra o tempo da consulta
			if($register_time)
				$this->queries_history[] = array('sql' => $sql, 'exec_time' => $msc);
			
			//Dispara um erro, caso ocorra
			$error = '';
			
			if($error = mysqli_error($this->connection)){
				if(!$disable_exception && !$this->in_transaction)
					throw new \Exception('<strong>'.$lang_file['common']['error'].':</strong> '.$error.'<br /><br /><strong>'.$lang_file['class_database']['sql_query'].':</strong> '.$sql);
				elseif($this->in_transaction)
					$this->transaction_failed = true;
			}
			
			//Registra as consultas da transação
			if($this->in_transaction)
				$this->transaction_queries[] = array('sql' => $sql, 'success' => !$this->transaction_failed, 'message' => $error);
			
			$return = false;
			
			if($this->sql_query){
				switch($this->sql_type){
					case 'SELECT':
					case 'SHOW':
						$i = 0;
						$this->queries_count++;

						while($elem = mysqli_fetch_array($this->sql_query)){
							//Utiliza valor de um campo como índice
							if(!empty($index_field) && array_key_exists($index_field, $elem))
								$i = $elem[$index_field];
							
							//Carrega os registros
							foreach($elem as $key => $val){
								$val = stripslashes($val);

								if(!is_int($key)){
									if($this->fetch_type == 'array')
										$this->records[$i][$key] = $val;
									else
										$this->records[$i]->$key = $val;
								}
							}

							$i++;
						}

						$this->row_count = mysqli_num_rows($this->sql_query);
						mysqli_free_result($this->sql_query);

						$return = true;
						break;
					
					case 'INSERT':
						$return = mysqli_insert_id($this->connection);
						break;
					
					case 'DELETE':
					case 'TRUNCATE':
						$return = mysqli_affected_rows($this->connection);
						break;
					
					case 'UPDATE':
						$return = true;
						break;
				}
			}
			
			return $return;
		}
		
		/**
		 * Realiza múltiplas consultas SQL ao banco de dados dentro de uma transação.
		 * 
		 * @param array $sql_array Vetor com as consultas SQL a serem realizadas.
		 * @return array Vetor com os índices 'success_count', que indica o total de sucessos nas consultas realizadas; e 'error', que indica a mensagem de erro que ocorreu durante a transação.
		 */
		public function multiple_query($sql_array = array()){
			$success_count = 0;
			
			$this->init_transaction();
			
			if(sizeof($sql_array)){
				foreach($sql_array as $sql){
					if($this->query($sql, 'object', false, true))
						$success_count++;
				}
			}
			
			$transaction_result = $this->end_transaction();
			return array('success_count' => $success_count, 'error' => $transaction_result['error']);
		}
		
		/**
		 * Carrega o resultado da última consulta SQL ao banco de dados.
		 * 
		 * @param int $index Índice do vetor de resultados que contém o valor a ser retornado.
		 * @return array|boolean Lista de registros resultantes ou FALSE em caso de falha. 
		 */
		public function result($index = null){
			if($this->sql_query && (($this->sql_type == 'SELECT') || ($this->sql_type == 'SHOW')))
				return is_int($index) ? $this->records[$index] : $this->records;
			else
				return false;
		}
		
		/**
		 * Retorna os campos de uma tabela do banco de dados.
		 * 
		 * @param string $table Tabela do banco de dados a ser carregada.
		 * @return array Vetor com a lista de campos da tabela.
		 */
		public function get_fields($table){
			$table_fields = array();
			
			$this->query('SHOW COLUMNS FROM '.$table);
			$columns = $this->result();
			
			foreach($columns as $column)
				$table_fields[] = $column->Field;
			
			return $table_fields;
		}
		
		/**
		 * Verifica se uma tabela do banco de dados possui o campo.
		 * 
		 * @param string $table Tabela do banco de dados a ser verificada. 
		 * @param string $field Campo a ser verificado na tabela.
		 * @return boolean TRUE caso o campo exista ou FALSE caso o campo não exista.
		 */
		public function has_field($table, $field){
			return in_array($field, $this->get_fields($table));
		}
		
		/**
		 * Verifica se um campo da tabela do banco de dados pode ser nulo.
		 * 
		 * @param string $table Tabela do banco de dados que contém o campo.
		 * @param string $field Campo a ser verificado.
		 * @return boolean TRUE caso o campo possa ser nulo ou FALSE caso o campo não possa ser nulo.
		 */
		public function field_null($table, $field){
			$this->query('SHOW COLUMNS FROM '.$table);
			$columns = $this->result();
			
			foreach($columns as $column){
				if($column->Field == $field)
					return (strtoupper($column->Null) == 'YES');
			}
			
			return false;
		}
		
		/**
		 * Verifica o tamanho máximo do campo de uma tabela do banco de dados caso ele seja do tipo VARCHAR.
		 * 
		 * @param string $table Tabela do banco de dados que contém o campo.
		 * @param string $field Campo a ser verificado.
		 * @return int Tamanho máximo do campo.
		 */
		public function field_length($table, $field){
			$this->query('SHOW COLUMNS FROM '.$table);
			$columns = $this->result();
			
			foreach($columns as $column){
				if($column->Field == $field){
					if(strtolower(substr($column->Type, 0, 7)) == 'varchar')
						return (int)reset(\Util\Regex::extract_parenthesis($column->Type));
				}
			}
			
			return 0;
		}
		
		/**
		 * Retorna a quantidade de registros retornados pela última consulta do tipo 'SELECT' ao banco de dados.
		 * 
		 * @return int Total de registros retornados.
		 */
		public function row_count(){
			return $this->row_count;
		}
		
		/**
		 * Retorna a última consulta SQL realizada.
		 * 
		 * @return string Consulta SQL.
		 */
		public function sql(){
			return $this->sql;
		}
		
		/**
		 * Retorna o ID do último registro inserido ao banco de dados.
		 * 
		 * @return int ID do último registro inserido. 
		 */
		public function last_id(){
			return ($this->sql_query && ($this->sql_type == 'INSERT')) ? mysqli_insert_id($this->connection) : null;
		}
		
		/**
		 * Verifica se houve sucesso na última consulta SQL ao banco de dados.
		 * 
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function success(){
			return (bool)$this->sql_query;
		}
		
		/**
		 * Carrega a quantidade de consultas SQL realizadas ao banco de dados.
		 * 
		 * @return int Quantidade de queries realizadas.
		 */
		public function queries_count(){
			return $this->queries_count;
		}
		
		/**
		 * Carrega a consulta SQL mais lenta realizada ao banco de dados.
		 * 
		 * @return string Consulta SQL mais lenta.
		 */
		public function slowest_query(){
			global $sys_language;
			
			$worst_time = 0;
			$worst_sql = '';
			
			foreach($this->queries_history as $item){
				if($item['exec_time'] > $worst_time){
					$worst_time = $item['exec_time'];
					$worst_sql = $item['sql'];
				}
			}
			
			return $sys_language->get('class_database', 'worst_query').': '.$worst_sql.' &mdash; ('.$worst_time.'s)';
		}
		
		/*-- Exportação --*/
		
		/**
		 * Gera um script de exportação do banco de dados.
		 * 
		 * @param array $tables Vetor com as tabelas a serem exportadas (todas se nenhuma for especificada).
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function export($tables = array()){
			$script = "--\n-- Exportação do banco de dados `".$this->db_name."`\n-- Gerado por Osiris em ".date('d/m/Y').", às ".date('H:i:s')."h\n--\n\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n/*!40101 SET NAMES utf8 */;\n\n/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;";
			
			//Pega todas as tabelas se elas nenhuma for especificada
			if(!sizeof($tables)){
				$this->query('SHOW TABLES', 'array');
				$result_tables = $this->result();
				
				foreach($result_tables as $result_table)
					$tables[] = reset($result_table);
			}

			//Percorre as tabelas
			foreach($tables as $table){
				//Carrega os dados da tabela
				$this->query('SELECT * FROM '.$table, 'array');
				$table_data = $this->result();
				
				//Apaga a tabela
				self::export_comment($script, 'Apaga a tabela `'.$table.'`');
				$script .= 'DROP TABLE IF EXISTS `'.$table.'`;';
				
				//Cria a tabela
				self::export_comment($script, 'Cria a tabela `'.$table.'`');
				$this->query('SHOW CREATE TABLE '.$table, 'array');
				$result = $this->result(0);
				
				$script .= $result['Create Table'];
				
				//Exporta os registros da tabela
				$table_length = sizeof($table_data);
				
				if($table_length){
					self::export_comment($script, 'Exporta registros da tabela `'.$table.'`');
					$script .= "/*!40000 ALTER TABLE `".$table."` DISABLE KEYS */;\n";
					
					$record_counter = 0;
					$table_fields = '';
					
					$first_record = $table_data[0];
					foreach($first_record as $field => $value)
						$table_fields .= '`'.$field.'`,';
					
					$script .= "INSERT INTO `".$table."` (".rtrim($table_fields, ',').") VALUES\n";
					
					foreach($table_data as $record){
						$record_counter++;
						$script .= "\t(";
						$values = "";
						
						foreach($record as $field => $value){
							if(($value === '') && $this->field_null($table, $field))
								$values .= "NULL,";
							else
								$values .= "'".addslashes($value)."',";
						}
						
						$record_counter_mod = $record_counter % self::MAX_EXPORT_RECORDS;
						
						$values_separator = (($record_counter === $table_length) || !$record_counter_mod) ? ';' : ',';
						$script .= rtrim($values, ',').")".$values_separator."\n";
						
						if(!$record_counter_mod)
							$script .= "\nINSERT INTO `".$table."` (".rtrim($table_fields, ',').") VALUES\n";
					}
					
					$script .= "/*!40000 ALTER TABLE `".$table."` ENABLE KEYS */;";
				}
			}
			
			$script .= "\n\n/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;";
			
			//Salva o arquivo
			$file = $this->db_name.'-'.date('YmdHis').'.sql';
			return \Storage\File::create('/temp', $file, $script);
		}
		
		/**
		 * Concatena um comentário ao script de exportação do banco de dados.
		 * 
		 * @param string $string Texto do script de exportação.
		 * @param string $comment Comentário a ser adicionado.
		 */
		private static function export_comment(&$string, $comment = ''){
			$string .= "\n\n--\n-- ".$comment."\n--\n\n";
		}
		
		/*-- Controle de transação --*/
		
		/**
		 * Inicia uma transação com o banco de dados.
		 * 
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function init_transaction(){
			$this->in_transaction = true;
			$this->transaction_failed = false;
			$this->transaction_queries = array();
			
			return mysqli_autocommit($this->connection, false);
		}
		
		/**
		 * Realiza definitivamente as consultas da transação no banco de dados.
		 * 
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		private function commit(){
			return ($this->in_transaction && !$this->transaction_failed) ? mysqli_commit($this->connection) : false;
		}
		
		/**
		 * Cancela as consultas da transação no banco de dados.
		 * 
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		private function rollback(){
			return ($this->in_transaction) ? mysqli_rollback($this->connection) : false;
		}
		
		/**
		 * Finaliza uma transação com o banco de dados.
		 * 
		 * @return array Vetor do resultado da transação, com os índices 'success', que indica se a transação ocorreu com sucesso; e 'error', que contém a mensagem de erro caso tenha ocorrido.
		 */
		public function end_transaction(){
			global $sys_language;
			
			if(!$this->transaction_failed){
				$this->commit();
				$result = array('success' => true, 'error' => '');
			}
			else{
				$this->rollback();
				$error = $sys_language->get('class_database', 'transaction_error');
				
				foreach($this->transaction_queries as $transaction_query){
					if(!$transaction_query['success']){
						$error .= '
							<br /><br /><strong>'.$sys_language->get('common', 'error').':</strong> '.$transaction_query['message'].'
							<br /><strong>'.$sys_language->get('class_database', 'sql_query').':</strong> '.$transaction_query['sql'].'
						';

						break;
					}
				}
				
				$result = array('success' => false, 'error' => $error);
			}
			
			$this->in_transaction = false;
			mysqli_autocommit($this->connection, true);
			
			return $result;
		}
		
		/**
		 * Retorna o valor a ser utilizado na consulta, caso ele possa ser nulo no banco de dados.
		 * 
		 * @param mixed $value Valor a ser inserido.
		 * @return string Valor na consulta.
		 */
		public static function value_or_null($value){
			return !empty($value) ? '"'.$value.'"' : 'NULL';
		}
	}
?>