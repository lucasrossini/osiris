<?php
	namespace Google;
	
	/**
	 * Classe para geração de gráficos através da API do Google Charts.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 11/03/2014
	*/
	
	class GoogleChart{
		private $id;
		private $title;
		private $type;
		private $columns;
		private $data;
		private $options;
		private $data_table;
		
		/**
		 * Instancia um gráfico.
		 * 
		 * @param string $id ID do gráfico.
		 * @param string $title Título do gráfico.
		 * @param string $type Tipo de gráfico (https://google-developers.appspot.com/chart/interactive/docs/gallery).
		 * @param array $columns Vetor das colunas da tabela de dados onde a chave contém o ID da coluna e o valor contém um vetor com os índices 'label', que indica o rótulo da coluna; e 'type', que indica o tipo de dados da coluna ('string', 'number', 'float' ou 'currency').
		 * @param array $data Vetor de dados da tabela onde a chave contém o ID da coluna relacionada e o valor contém um vetor com os registros da coluna. 
		 * @param array $options Vetor de opções do gráfico (https://google-developers.appspot.com/chart/interactive/docs/customizing_charts).
		 */
		public function __construct($id, $title, $type, $columns, $data, $options = array()){
			$this->id = $id;
			$this->title = $title;
			$this->type = $type;
			$this->columns = $columns;
			$this->data = $data;
			
			$options['title'] = $title;
			$this->options = json_encode($options);
			
			$this->data_table = array();
		}
		
		/**
		 * Prepara os dados no formato DataTable (https://google-developers.appspot.com/chart/interactive/docs/reference#dataparam).
		 */
		private function prepare_data(){
			$this->data_table['cols'] = array();
			
			foreach($this->columns as $column_id => $column_info){
				switch($column_info['type']){
					case 'number':
					case 'float':
					case 'currency':
						$column_info['type'] = 'number';
						break;
					
					default:
						$column_info['type'] = 'string';
				}
				
				$this->data_table['cols'][] = array_merge(array('id' => $column_id), $column_info);
			}
			
			$rows_count = sizeof(reset($this->data));
			
			for($i = 0; $i < $rows_count; $i++){
				$c = array();
				
				foreach($this->columns as $column_id => $column_info){
					$value = $this->data[$column_id][$i];
					$c[] = array('v' => $value);
				}
				
				$this->data_table['rows'][] = array('c' => $c);
			}
			
			$this->data_table = json_encode($this->data_table);
		}
		
		/**
		 * Desenha o gráfico.
		 * 
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public function draw($echo = true){
			//Nome da função de desenho
			$function_name = 'draw_chart_'.$this->id;
			
			//Prepara os dados
			$this->prepare_data();
			
			//Script de formatação
			$formatter_script = '';
			$i = 0;
			
			foreach($this->columns as $column_info){
				switch($column_info['type']){
					case 'float':
						$formatter_script .= '
							formatter = new google.visualization.NumberFormat({negativeColor: "red", pattern: "#,###.##"});
							formatter.format(data, '.$i.');
						';
						
						break;
					
					case 'currency':
						$formatter_script .= '
							formatter = new google.visualization.NumberFormat({negativeColor: "red", pattern: "R$ #,###.##"});
							formatter.format(data, '.$i.');
						';
						
						break;
				}
				
				$i++;
			}
			
			//Monta o HTML
			$html = '
				<div id="'.$this->id.'" class="google-chart"></div>
				
				<script>
					//Carrega a API
					google.load("visualization", "1", {packages:["corechart"]});
					google.setOnLoadCallback('.$function_name.');
					
					//Função de desenho do gráfico
					function '.$function_name.'(){
						var data = new google.visualization.DataTable('.$this->data_table.');
						var chart = new google.visualization.'.$this->type.'(document.getElementById("'.$this->id.'"));
						var formatter;
						
						'.$formatter_script.'
						
						chart.draw(data, '.$this->options.');
					}
				</script>
			';
			
			if($echo)
				echo $html;
			else
				return $html;
		}
		
		/**
		 * Carrega o script da API do Google Charts.
		 * 
		 * @return string Elemento script.
		 */
		public static function get_script(){
			return '<script src="https://www.google.com/jsapi"></script>';
		}
	}
?>