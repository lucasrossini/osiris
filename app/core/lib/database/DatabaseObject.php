<?php
	namespace Database;
	
	/**
	 * Classe que manipula objetos de banco de dados.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 16/04/2014
	*/
	
	abstract class DatabaseObject extends \System\API{
		const TABLE_NAME = null;
		const BASE_PATH = null;
		
		protected static $multilang_fields = array();
		
		protected $id;
		protected $loaded = false;
		protected $valid = false;
		
		/**
		 * Instancia um objeto DAO.
		 * 
		 * @param int $id ID do registro no banco de dados.
		 * @param boolean $autoload Define se os dados do registro serão carregados automaticamente ou somente em sua primeira utilização.
		 */
		public function __construct($id = null, $autoload = false){
			$this->id = (int)$id;
			
			if(!is_null($id) && $autoload)
				$this->load($id, $autoload);
		}
		
		/**
		 * Monta o objeto DAO atribuindo valores carregados de seu registro no banco de dados aos seus atributos.
		 * 
		 * @param int $id ID do registro no banco de dados.
		 * @param boolean $autoload Define se os dados do registro serão carregados automaticamente ou somente em sua primeira utilização.
		 * @return array|boolean Vetor com os valores do registro resultante da consulta ou FALSE em caso de inexistência.
		 */
		public function load($id, $autoload = false){
			if($record = $this->load_data($id)){
				$common_attributes = array('id', 'loaded', 'valid');
				$this->loaded = $this->valid = true;
				
				$class_name = get_class($this);
				$vars = get_class_vars($class_name);
				
				foreach($vars as $attr => $value){
					if(!in_array($attr, $common_attributes) && $record->$attr){
						//Múltiplos idiomas
						if(in_array($attr, $class_name::$multilang_fields)){
							$field_languages = unserialize($record->$attr);
							
							if(is_array($field_languages)){
								$current_lang = \System\Language::get_current_lang();
								$this->$attr = array_key_exists($current_lang, $field_languages) ? $field_languages[$current_lang] : $field_languages[\System\Language::$default_lang];
							}
							else{
								$this->$attr = $record->$attr;
							}
						}
						else{
							$this->$attr = $record->$attr;
						}
					}
				}
				
				return $record;
			}
			
			return false;
		}
		
		/**
		 * Carrega os valores da classe DAO estendida.
		 * 
		 * @param int $id ID do registro estendido no banco de dados.
		 */
		protected function extend($id){
			$reflection_class = new \ReflectionClass($this);
			$reflection_parent = $reflection_class->getParentClass();
			$parent_name = $reflection_parent->getName();
			
			$parent_obj = new $parent_name($id, true);
			$vars = get_class_vars($parent_name);
			
			$common_attributes = array('id', 'loaded', 'valid');
			
			foreach($vars as $attr => $value){
				if(!in_array($attr, $common_attributes)){
					if(!$reflection_parent->getProperty($attr)->isStatic())
						$this->$attr = $parent_obj->$attr;
				}
			}
		}
		
		/**
		 * Carrega o registro no banco de dados.
		 * 
		 * @param int $id ID do registro no banco de dados.
		 * @return array|boolean Vetor com os valores do registro resultante da consulta ou FALSE em caso de inexistência. 
		 */
		protected function load_data($id){
			global $db;
			
			$db->query('SELECT * FROM '.self::get_constant('TABLE_NAME').' WHERE id = '.(int)$id);
			return $db->row_count() ? $db->result(0) : false;
		}
		
		/**
		 * Recarrega um registro.
		 */
		public function refresh(){
			if($this->id)
				$this->load($this->id);
		}
		
		/**
		 * Transforma um objeto da classe em um vetor.
		 * 
		 * @param object $object Objeto a ser convertido.
		 * @return array Vetor do objeto.
		 */
		public static function to_array($object){
			$array = array();
			
			if(is_object($object)){
				if(!$object->loaded){
					$class = get_class($object);
					$reflection_class = new \ReflectionClass($class);
					
					if($reflection_class->isSubclassOf('DatabaseObject'))
						$object->load($object->id);
				}
				
				if(isset($object->id))
					$array['id'] = $object->id;
				
				unset($object->id, $object->loaded, $object->valid);

				foreach($object as $key => $value)
					$array[$key] = $value;
				
				$object = $array;
			}
			
			return is_array($object) ? array_map(__METHOD__, $object) : $object;
		}
		
		/**
		 * Retorna o valor de uma constante definida na classe.
		 * 
		 * @param string $constant Nome da constante.
		 * @return mixed|boolean Valor da constante caso a constante exista ou FALSE caso a constante não exista.
		 */
		public static function get_constant($constant){
			$reflection_class = new \ReflectionClass(get_called_class());
			return $reflection_class->hasConstant($constant) ? $reflection_class->getConstant($constant) : false;
		}
		
		/*-- CRUD --*/
		
		/**
		 * Retorna o valor de um determinado atributo do objeto.
		 * 
		 * @param string $attr Nome do atributo a ser retornado.
		 * @param mixed $index Índice desejado do vetor retornado caso o valor do atributo seja um vetor.
		 * @throws Exception Atributo inválido da classe.
		 * @return mixed Valor do atributo.
		 */
		public function get($attr, $index = null){
			global $sys_language;
			
			if(empty($attr))
				return false;
			
			$class_name = get_class($this);
			$vars = get_class_vars($class_name);
			
			if(array_key_exists($attr, $vars)){
				if(!$this->loaded){
					$this->loaded = true;
					$this->load($this->id);
				}
				
				if(is_array($this->$attr) && !is_null($index)){
					$array = $this->$attr;
					return $array[$index];
				}
				else{
					if(!$this->$attr->loaded && is_object($this->$attr) && (get_parent_class($this->$attr) == 'DatabaseObject')){
						$this->$attr->loaded = true;
						$this->$attr->load($this->$attr->id);
					}
					
					return $this->$attr;
				}
			}
			else{
				throw new \Exception(sprintf($sys_language->get('class_dao', 'attr_error'), $attr, $class_name));
			}
		}
		
		/**
		 * Define o valor de um determinado atributo da classe.
		 * 
		 * @param string|array $attr Nome (ou vetor de nomes) do atributo a ser definido.
		 * @param mixed|array $value Valor (ou vetor de valores) a ser gravado no atributo.
		 * @throws Exception Atributo inválido da classe.
		 */
		public function set($attr, $value){
			global $sys_language;
			
			$class_name = get_class($this);
			$vars = get_class_vars($class_name);
			
			if(is_array($attr) && is_array($value)){
				$i = 0;
				
				foreach($attr as $attr_item)
					$this->$attr_item = $value[$i++];
			}
			elseif(($attr != 'id') && array_key_exists($attr, $vars)){
				$this->$attr = $value;
			}
			else{
				throw new \Exception(sprintf($sys_language->get('class_dao', 'attr_error'), $attr, $class_name));
			}
		}
		
		/**
		 * Salva o objeto no banco de dados.
		 * 
		 * @param boolean $get_error Define se o erro deve ser retornado, caso ocorra.
		 * @return int|boolean|string Resultado da consulta realizada ao banco de dados ou a mensagem de erro caso exista e seja retornada.
		 */
		public function save($get_error = false){
			global $db;
			$table = self::get_constant('TABLE_NAME');
			
			$class_name = get_class($this);
			$vars = get_class_vars($class_name);
			
			$table_fields = $db->get_fields($table);
			
			if(!$this->id){ //INSERT
				foreach($vars as $field => $value){
					if(($field != 'id') && in_array($field, $table_fields)){
						$sql_fields .= '`'.$field.'`, ';
						$sql_values .= (((string)$this->get($field) === '') && $db->field_null($table, $field)) ? 'NULL, ' : '"'.$this->get($field).'", ';
					}
				}
				
				$sql = 'INSERT INTO '.$table.' ('.rtrim($sql_fields, ', ').') VALUES ('.rtrim($sql_values, ', ').')';
			}
			else{ //UPDATE
				foreach($vars as $field => $value){
					if(($field != 'id') && in_array($field, $table_fields))
						$sql_values .= (((string)$this->get($field) === '') && $db->field_null($table, $field)) ? '`'.$field.'` = NULL, ' : '`'.$field.'` = "'.$this->get($field).'", ';
				}
				
				$sql = 'UPDATE '.$table.' SET '.rtrim($sql_values, ', ').' WHERE id = '.$this->id;
			}
			
			try{
				$query_result = $db->query($sql);

				if(!$this->id)
					$this->load($query_result);
				else
					$this->refresh();

				return $query_result;
			}
			catch(Exception $e){
				if($get_error)
					return $e->getMessage();
			}
			
			return false;
		}
		
		/**
		 * Apaga o objeto do banco de dados.
		 * 
		 * @param boolean $get_error Define se o erro deve ser retornado, caso ocorra.
		 * @return boolean|string TRUE em caso de sucesso ou FALSE em caso de falha; ou a mensagem de erro caso exista e seja retornada.
		 */
		public function delete($get_error = false){
			global $db;
			
			if($this->id){
				try{
					//Apaga os registros relacionados
					$class = get_called_class();
					$before_queries_result = $db->multiple_query($class::get_before_delete_queries($this->id));
					
					if(!empty($before_queries_result['error']))
						return $before_queries_result['error'];
					
					//Apaga o registro principal
					$result = $db->query('DELETE FROM '.self::get_constant('TABLE_NAME').' WHERE id = '.$this->id);
					
					//Limpa o objeto
					$this->clear();
					
					return ($result > 0) ? true : false;
				}
				catch(Exception $e){
					if($get_error)
						return $e->getMessage();
				}
			}
			
			return false;
		}
		
		/**
		 * Limpa o objeto, removendo todos os valores de seus atributos.
		 */
		public function clear(){
			$class_name = get_class($this);
			$vars = get_class_vars($class_name);
			
			foreach($vars as $attr => $value)
				$this->$attr = null;
			
			$this->loaded = false;
			$this->valid = false;
		}
		
		/**
		 * Carrega uma lista de objetos da classe.
		 * 
		 * @param string $sql Consulta SQL a ser realizada.
		 * @param int $offset A partir de qual registro da consulta devem ser retornados os resultados.
		 * @param int $count Quantidade de registros a serem carregados (0 para todos).
		 * @param boolean $paginate Define se os registros devem ser paginados em $count registros por página.
		 * @param boolean $autoload Define se os dados dos registros serão carregados automaticamente ou somente em sua primeira utilização.
		 * @param boolean $only_ids Define se o que deve ser retornado são apenas os IDs dos registros carregados.
		 * @param string $force_class Forçar o nome da classe a ser utilizada ao invés da que chama o método.
		 * @return array Vetor com os índices 'results', que contém a lista de objetos instanciados da classe que chamou o método, 'paginator', que contém o objeto de paginação dos resultados; e 'count', que contém o total de registros retornados.
		 */
		public static function load_all($sql = '', $offset = 0, $count = 0, $paginate = false, $autoload = false, $only_ids = false, $force_class = ''){
			global $db;
			$class = !empty($force_class) ? $force_class : get_called_class();
			
			if(empty($sql))
				$sql = 'SELECT id FROM '.$class::get_constant('TABLE_NAME');
			
			//Paginação
			if($count > 0){
				$db->query('SELECT COUNT(*) AS total FROM ('.$sql.') AS sub');
				$items_total = $db->result(0)->total;
			}
			
			if($paginate && ($count > 0)){
				$paginator = new Paginator($items_total, $count);
				$sql .= $paginator->get_limit();
			}
			else{
				if($count > 0)
					$sql .= ' LIMIT '.(int)$offset.','.(int)$count;
				
				$paginator = new Paginator();
			}
			
			//Carrega os objetos
			$db->query($sql, 'object', $only_ids ? 'id' : '');
			$records = $db->result();
			
			if(!$only_ids){
				$objects_list = array();
				
				foreach($records as $record)
					$objects_list[] = new $class($record->id, $autoload);
			}
			else{
				$objects_list = array_keys($records);
			}
			
			return array('results' => $objects_list, 'paginator' => $paginator, 'count' => !$items_total ? sizeof($records) : $items_total);
		}
		
		/**
		 * Realiza uma busca de objetos da classe.
		 * 
		 * @param string $query Palavras-chave da pesquisa.
		 * @param array $fields Vetor com os campos da tabela do banco de dados considerados pela pesquisa (LIKE). Se vazio, considera todos os campos da tabela.
		 * @param array $extra_fields Vetor com as cláusulas definidas na consulta SQL a ser realizada, onde a chave é o campo da tabela e o valor é o valor do campo da tabela.
		 * @param int $count Quantidade de registros a serem carregados (0 para todos).
		 * @param boolean $paginate Define se os registros devem ser paginados em $count registros por página.
		 * @param array $order_fields Vetor com as cláusulas de ordenação (ORDER BY) da consulta SQL a ser realizada, onde a chave é o campo a ser ordenado e o valor é o tipo de ordenação (ASC ou DESC).
		 * @param boolean $force_and Indica se as palavras-chave da pesquisa devem estar incluídas em todos os campos considerados da tabela do banco de dados.
		 * @param boolean $autoload Define se os dados dos registros serão carregados automaticamente ou somente em sua primeira utilização.
		 * @return array Vetor com os índices 'results', que contém a lista de objetos resultantes da classe que chamou o método; 'paginator', que contém o objeto de paginação dos resultados; e 'count', que contém o total de registros retornados.
		 */
		public static function search($query = '', $fields = array(), $extra_fields = array(), $count = 0, $paginate = true, $order_fields = array(), $force_and = false, $autoload = false){
			global $db;
			
			$called_class = get_called_class();
			
			$where_clause = 'true';
			$where_connector = $force_and ? 'AND' : 'OR';
			$order_clause = '';
			
			//Campos de pesquisa
			if(!empty($query)){
				$where_clause = '';
				
				if(!sizeof($fields))
					$fields = $db->get_fields(self::get_constant('TABLE_NAME'));
				
				foreach($fields as $field)
					$where_clause .= '(`'.$field.'` LIKE "%'.$query.'%") '.$where_connector.' ';
				
				$where_clause = '('.rtrim($where_clause, ' '.$where_connector.' ').')';
			}
			
			//Valores fixados
			if(is_array($extra_fields) && sizeof($extra_fields)){
				foreach($extra_fields as $field => $value){
					if(is_array($value) && sizeof($value)){
						$in_values = '';
						
						foreach($value as $value_item)
							$in_values .= '"'.$value_item.'", ';
						
						$where_clause .= ' AND `'.$field.'` IN ('.rtrim($in_values, ', ').')';
					}
					else{
						$aux_value = strtoupper(trim($value));
						
						switch($aux_value){
							case 'NULL':
								$where_clause .= ' AND `'.$field.'` IS NULL';
								break;
							
							case 'NOT NULL':
								$where_clause .= ' AND `'.$field.'` IS NOT NULL';
								break;
							
							default:
								if(in_array(substr($aux_value, 0, 1), array('>', '<', '!')))
									$where_clause .= ' AND `'.$field.'` '.$value;
								else
									$where_clause .= ' AND `'.$field.'` = "'.$value.'"';
						}
					}
				}
			}
			
			//Ordenação
			if(is_array($order_fields) && sizeof($order_fields)){
				$order_clause = ' ORDER BY ';
				$order_methods = array('ASC', 'DESC');
			
				foreach($order_fields as $field => $sort_type){
					if(strtoupper($field) == 'RAND'){
						$seed = !empty($sort_type) ? (int)$sort_type : RANDOM_SEED;
						$order_clause .= ' RAND('.$seed.'), ';
					}
					else{
						if(!in_array(strtoupper($sort_type), $order_methods))
							$sort_type = 'ASC';
			
						$order_clause .= ' `'.$field.'` '.strtoupper($sort_type).', ';
					}
				}
			
				$order_clause = rtrim($order_clause, ', ');
			}
			
			$limit_clause = (!$paginate && ($count > 0)) ? ' LIMIT 0,'.(int)$count : '';
			$sql = 'SELECT id FROM '.self::get_constant('TABLE_NAME').' WHERE '.$where_clause.$order_clause.$limit_clause;
			
			//Paginação
			if($paginate && ($count > 0)){
				$db->query('SELECT COUNT(*) AS total FROM ('.$sql.') AS sub');
				$items_total = $db->result(0)->total;

				$paginator = new Paginator($items_total, $count);
				$sql .= $paginator->get_limit();
			}
			else{
				$paginator = new Paginator();
			}
			
			$db->query($sql);
			$records = $db->result();
			$objects_list = array();
			
			foreach($records as $record)
				$objects_list[] = new $called_class($record->id, $autoload);
			
			return array('results' => $objects_list, 'paginator' => $paginator, 'count' => !$items_total ? sizeof($records) : $items_total);
		}
		
		/**
		 * Monta as consultas SQL a serem realizadas antes da remoção do registro.
		 * 
		 * @param int $id ID do registro.
		 * @return array Vetor com as consultas SQL.
		 */
		public static function get_before_delete_queries($id){
			return array();
		}
		
		/**
		 * Monta as consultas SQL a serem realizadas depois da remoção do registro.
		 * 
		 * @param int $id ID do registro.
		 * @return array Vetor com as consultas SQL.
		 */
		public static function get_after_delete_queries($id){
			return array();
		}
		
		/**
		 * Retorna as partes da URL a partir do caminho base da URL do objeto.
		 * 
		 * @param string $url URL a ser processada.
		 * @return array Vetor com as partes da URL.
		 */
		protected static function get_current_url_pieces($url){
			$url = preg_replace("/".str_replace('/', '\/', self::get_constant('BASE_PATH'))."/", '', $url, 1);
			$url_pieces = explode('/', $url);
			
			$aux_pieces = array();
			
			foreach($url_pieces as $url_piece)
				$aux_pieces[] = \Security\Sanitizer::sanitize(urldecode($url_piece));
			
			return $aux_pieces;
		}
		
		/*-- Social --*/
		
		/**
		 * Carrega as tags do Facebook para um registro.
		 * 
		 * @param int $id ID do registro na tabela do banco de dados.
		 * @return string Tags do Facebook.
		 */
		public static function get_facebook_tags($id){
			$called_class = get_called_class();
			
			$object = new $called_class($id);
			$data = $called_class::$facebook_data;
			
			$image_source = $data['image'] ? $object->get($data['image']) : '/site/media/images/facebook/logo.png';
			
			$tags = '
				<meta property="og:title" content="'.$object->get($data['title']).'" />
				<meta property="og:description" content="'.$object->get($data['description']).'" />
				<meta property="og:type" content="'.$data['type'].'" />
				<meta property="og:url" content="'.BASE.'/'.ltrim($object->get($data['url']), '/').'" />
				<meta property="og:image" content="'.BASE.$image_source.'" />
			';
				
			return $tags;
		}
		
		/**
		 * Retorna a URL que possui as meta tags do Facebook para curtir uma página de um registro.
		 * 
		 * @return string URL com as meta tags.
		 */
		public function get_facebook_like_url(){
			return BASE.'/app/core/util/facebook-like?class='.get_class($this).'&id='.$this->id;
		}
		
		/**
		 * Carrega as tags do Google Plus para um registro.
		 * 
		 * @param int $id ID do registro na tabela do banco de dados.
		 * @return string Tags do Facebook.
		 */
		public static function get_gplus_tags($id){
			$called_class = get_called_class();
			
			$object = new $called_class($id);
			$data = $called_class::$gplus_data;
			
			$image_source = $data['image'] ? $object->get($data['image']) : '/site/media/images/facebook/logo.png';
				
			$tags = '
				<meta itemprop="name" content="'.$object->get($data['name']).'" />
				<meta itemprop="description" content="'.$object->get($data['description']).'" />
				<meta itemprop="image" content="'.BASE.$image_source.'" />
			';
				
			return $tags;
		}
		
		/*-- RSS --*/
		
		/**
		 * Verifica se a URL é um RSS válido.
		 * 
		 * @param string $url URL do RSS, sem o caminho base ('/rss') e sem a extensão ('.xml').
		 * @return boolean TRUE se o RSS for válido ou FALSE se o RSS for inválido.
		 */
		public static function check_rss($url){
			$called_class = get_called_class();
			return ($url === $called_class::$rss_data['url']);
		}
		
		/**
		 * Carrega o RSS da seção.
		 * 
		 * @return array Vetor com os itens de RSS.
		 */
		public static function get_rss(){
			global $db;
			$called_class = get_called_class();
			
			$rss_data = array(
				'title' => $called_class::$rss_data['name'],
				'url' => BASE.$called_class::BASE_PATH,
				'items' => array(),
				'description' => $called_class::$rss_data['description']
			);
			
			$sql = $called_class::$rss_data['sql'] ? $called_class::$rss_data['sql'] : 'SELECT id FROM '.$called_class::TABLE_NAME.' ORDER BY id DESC';
			$db->query($sql);
			$records = $db->result();
			
			$object = new $called_class();
			
			foreach($records as $record){
				$object->load($record->id);
				
				$description = '
					'.$object->get_image_tag($called_class::$rss_data['item_attr']['image']).'
					'.$object->get($called_class::$rss_data['item_attr']['description']).'
				';
				
				$rss_data['items'][] = array('title' => $object->get($called_class::$rss_data['item_attr']['title']), 'description' => $description, 'url' => BASE.$object->get('url'), 'image' => $object->get($called_class::$rss_data['item_attr']['image']), 'date' => $object->get($called_class::$rss_data['item_attr']['date']), 'time' => $object->get($called_class::$rss_data['item_attr']['time']));
			}
			
			return $rss_data;
		}
		
		/**
		 * Retorna uma tag de imagem para o objeto se ele possui um valor para imagem.
		 * 
		 * @param string $attr Nome do atributo de imagem.
		 * @return string Tag de imagem.
		 */
		protected function get_image_tag($attr = 'image'){
			return (!empty($this->$attr) && $this->get($attr)) ? '<img src="'.BASE.$this->get($attr).'" />' : '';
		}
		
		/*-- Formatação --*/
		
		/**
		 * Cria um objeto com valor monetário formatado.
		 * 
		 * @param float $value Valor a ser formatado.
		 * @return stdClass|string Objeto com o valor original 'original' e com o valor formatado 'formatted'.
		 */
		protected static function create_money_obj($value){
			if((string)$value !== ''){
				$value_obj = new \stdClass();
				$value_obj->original = $value;
				$value_obj->formatted = \Formatter\Number::money($value);
			}
			else{
				$value_obj = null;
			}
			
			return $value_obj;
		}
		
		/**
		 * Cria um objeto com data formatada.
		 * 
		 * @param string $date Data a ser formatada.
		 * @return stdClass|string Objeto com a data original 'original' e com a data formatada 'formatted'.
		 */
		protected static function create_date_obj($date){
			if(!empty($date)){
				$date_obj = new \stdClass();
				$date_obj->original = $date;
				$date_obj->formatted = \DateTime\Date::convert($date);
			}
			else{
				$date_obj = null;
			}
			
			return $date_obj;
		}
		
		/**
		 * Cria um objeto com horário formatado.
		 * 
		 * @param string $time Horário a ser formatado.
		 * @return stdClass|string Objeto com o horário original 'original' e com o horário formatado 'formatted'.
		 */
		protected static function create_time_obj($time){
			if(!empty($time)){
				$time_obj = new \stdClass();
				$time_obj->original = $time;
				$time_obj->formatted = \DateTime\Time::sql2time($time).'h';
			}
			else{
				$time_obj = null;
			}
			
			return $time_obj;
		}
		
		/**
		 * Cria um objeto com gênero (sexo) formatado.
		 * 
		 * @param int $gender Gênero a ser formatado.
		 * @return stdClass|string Objeto com o gênero original 'original' e com o gênero formatado 'formatted'.
		 */
		protected static function create_gender_obj($gender){
			if(!empty($gender)){
				$gender_obj = new \stdClass();
				$gender_obj->original = $gender;
				$gender_obj->formatted = \Formatter\String::genre($gender);
			}
			else{
				$gender_obj = null;
			}
			
			return $gender_obj;
		}
		
		/**
		 * Monta endereço completo formatado.
		 * 
		 * @param string $street Rua.
		 * @param string $number Número.
		 * @param string $complement Complemento.
		 * @param string $neighborhood Bairro.
		 * @param string $cep CEP.
		 * @param City $city_obj Cidade.
		 * @param State $state_obj Estado.
		 * @return string Endereço completo formatado.
		 */
		protected static function format_address($street, $number, $complement, $neighborhood, $cep, $city_obj, $state_obj){
			$address = '';
			
			if(!empty($street) || !empty($neighborhood) || $city_obj->get('id') || $state_obj->get('id')){
				if(!empty($street)){
					$number = !empty($number) ? ', '.$number : '';
					$complement = !empty($complement) ? ' / '.$complement : '';
					
					$address .= $street.$number.$complement.'<br />';
				}
				
				if(!empty($neighborhood) || !empty($cep)){
					$cep = (!empty($neighborhood) && !empty($cep)) ? ' - '.$cep : $cep;
					$address .= $neighborhood.$cep.'<br />';
				}
				
				if(!empty($city_obj))
					$address .= !empty($state_obj) ? $city_obj->get('name').', ' : $city_obj->get('name');
				
				if(!empty($state_obj))
					$address .= $state_obj->get('acronym');
			}
			
			return $address;
		}
	}
?>