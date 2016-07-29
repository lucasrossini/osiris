<?php
	namespace UI;
	
	/**
	 * Classe para geração de relatórios.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 21/09/2011
	*/
	
	class Report{
		private $title;
		private $header_every_page;
		private $show_total_reg;
		private $show_line_numbers;
		private $is_landscape;
		private $filters;
		private $per_page;
		private $num_pages;
		private $total_regs;
		private $logo;
		private $fields;
		private $data;
		private $total_groups;
		private $subgroup;
		private $subgroup_totals;
		private $html;
		private $has_pdf;
		
		/**
		 * Instancia um objeto de relatório.
		 * 
		 * @param string $title Título do relatório.
		 * @param string $logo Caminho completo do arquivo de imagem a ser exibido como logomarca do relatório.
		 * @param boolean $header_every_page Define se o cabeçalho deve ser exibido em cada página do relatório.
		 * @param boolean $show_total_reg Define se o número total de registros deve ser exibido no cabeçalho do relatório.
		 * @param boolean $has_pdf Define se um botão para geração de PDF deve ser exibido no cabeçalho do relatório.
		 */
		public function __construct($title, $logo = '', $header_every_page = false, $show_total_reg = true, $has_pdf = false){
			$this->title = $title;
			$this->logo = $logo;
			$this->header_every_page = $header_every_page;
			$this->show_total_reg = $show_total_reg;
			
			$this->show_line_numbers = false;
			$this->is_landscape = false;
			$this->has_pdf = $has_pdf;
			
			$this->filters = new \stdClass();
			$this->filters->text = '----';
			$this->filters->array = array();
			
			$this->html = '';
		}
		
		/**
		 * Adiciona campos ao relatório.
		 * 
		 * @param array $fields Vetor multidimensional com os campos do relatório, onde a chave é o nome do campo no resultado da consulta SQL ao banco de dados e o valor é um vetor com os índices 'name', que indica o nome a ser exibido pelo campo no relatório; e 'type', que indica o tipo de formatação a ser realizado sobre o valor do campo.
		 */
		public function add_fields($fields = array()){
			$this->fields = $fields;
		}
		
		/**
		 * Adiciona dados ao relatório.
		 * 
		 * @param array $data Vetor com o resultado da consulta SQL ao banco de dados.
		 */
		public function add_data($data = array()){
			$this->data = $data;
			$this->total_regs = sizeof($this->data);
		}
		
		/**
		 * Adiciona um subgrupo ao relatório, que irá agrupar registros com o mesmo valor indicado.
		 * 
		 * @param array $subgroup Vetor com os índices 'field', que indica o campo do banco de dados que será referência para o agrupamento dos registros de mesmo valor; e 'type', que indica o tipo de formatação a ser realizado sobre o valor do campo do grupo.
		 */
		public function add_subgroup($subgroup = array()){
			$this->subgroup = $subgroup;
		}
		
		/**
		 * Adiciona um grupo de total de valores ao relatório.
		 * 
		 * @param array $total_groups Vetor multidimensional com os totais do relatório, onde a chave é o nome do campo no resultado da consulta SQL ao banco de dados a ser calculado e o valor é um vetor com os índices 'group_type', que indica o tipo de cálculo a ser realizado pelo valor campo do banco de dados; 'type', que indica o tipo de formatação a ser realizado sobre o valor do campo; e 'label', que indica o rótulo descritivo que será exibido antes do valor total.
		 */
		public function add_total_groups($total_groups = array()){
			$this->total_groups = $total_groups;
		}
		
		/**
		 * Adiciona um grupo de total de valores de um subgrupo ao relatório.
		 * 
		 * @param array $total_groups Vetor multidimensional com os totais do relatório, onde a chave é o nome do campo no resultado da consulta SQL ao banco de dados a ser calculado e o valor é um vetor com os índices 'group_type', que indica o tipo de cálculo a ser realizado pelo valor campo do banco de dados; 'type', que indica o tipo de formatação a ser realizado sobre o valor do campo; e 'label', que indica o rótulo descritivo que será exibido antes do valor total.
		 */
		public function add_subgroup_totals($subgroup_totals = array()){
			$this->subgroup_totals = $subgroup_totals;
		}
		
		/**
		 * Define o número de registros por página do relatório.
		 * 
		 * @param int $per_page Quantidade de registros por página.
		 */
		public function set_per_page($per_page){
			$this->per_page = $per_page;
		}
		
		/**
		 * Adiciona filtros a serem exibidos no cabeçalho do relatório.
		 * 
		 * @param string $type Tipo de filtro, que pode ser 'period' ou 'month-year'.
		 * @param array $values Vetor com os valores do filtro.
		 */
		public function add_filter($type, $values = array()){
			$new_filter = '';
			
			switch($type){
				case 'period';
					if(empty($values['init_date']) && empty($values['end_date'])){
						$values['init_date'] = '01/'.date('m/Y');
						$values['end_date'] = date('d/m/Y');
					}
					
					$new_filter = 'Período de '.$values['init_date'].' à '.$values['end_date'];
					break;
				
				case 'month-year':
					$new_filter = \DateTime\Date::month_name($values['month']).' de '.$values['year'];
					break;
				
				default:
					break;
			}
			
			if(!empty($new_filter)){
				$this->filters->array[$type] = $values;
				$this->filters->text = (($this->filters->text == '') || ($this->filters->text == '----')) ? $new_filter : ', '.$new_filter;
			}
		}
		
		/**
		 * Carrega um valor de um filtro.
		 * 
		 * @param string $type Tipo de filtro.
		 * @param string $value Nome do valor do filtro.
		 * @return string|boolean Valor do filtro em caso de sucesso ou FALSE em caso de falha.
		 */
		public function get_filter_value($type, $value){
			if(array_key_exists($type, $this->filters->array) && array_key_exists($value, $this->filters->array[$type]))
				return $this->filters->array[$type][$value];
			
			return false;
		}
		
		/**
		 * Exibe o número do registro no relatório.
		 * 
		 * @param boolean $set Define se os números dos registros devem ser exibidos no relatório.
		 */
		public function show_line_numbers($set){
			$this->show_line_numbers = $set;
		}
		
		/**
		 * Define o relatório como formato paisagem.
		 * 
		 * @param boolean $set Define se o relatório é em formato paisagem.
		 */
		public function set_landscape($set){
			$this->is_landscape = $set;
		}
		
		/**
		 * Prepara o HTML do relatório.
		 * 
		 * @param boolean $paginate Define se o relatório deve efetuar paginação.
		 */
		private function prepare_report($paginate = true){
			$this->num_pages = ceil($this->total_regs / $this->per_page) >= 1 ? ceil($this->total_regs / $this->per_page) : 1;
			
			$aux = 0;
			$aux_group = array();
			$row_count = 0;
			$pdf_link = $this->has_pdf ? '<a href="'.URL.'&pdf=1" title="Gerar PDF" class="pdf" style="margin-left:10px"><img src="pdf-icon.gif" alt="Gerar PDF" /></a>' : '';
			
			if(!$paginate)
				$this->num_pages = 1;
			
			//Páginas
			for($page = 1; $page <= $this->num_pages; $page++){
				if(!$paginate){
					$this->html .= '<div class="pagina-landscape">';
					$max = $this->total_regs;
					$start = 0;
				}
				else{
					$this->html .= '<div class="page">';
					
					$start = $aux;
					$remaining_registers = $this->total_regs - $aux;
					$max = ($remaining_registers > $this->per_page) ? $this->per_page : $remaining_registers;
					$max += $aux;
				}
				
				//Cabeçalho
				if(($page === 1) || ($this->header_every_page)){
					$this->html .= '
						<div class="header">
							<table>
								<tr>
									<td class="logo last-line" rowspan="3"><img src="'.$this->logo.'" alt="Logomarca" /></td>
									<td><h1 class="title">'.$this->title.'</h1></td>
									<td style="text-align:right">
										<a href="javascript:print()" title="Imprimir" class="imprimir"><img src="print-icon.gif" alt="Imprimir" /></a>
										'.$pdf_link.'
									</td>
								</tr>
								
								<tr>
									<td colspan="2"><strong>Hora:</strong> '.date('H:i:s').'</td>
								</tr>
								
								<tr>
									<td class="last-line"><strong>Data:</strong> '.DATA_ATUAL_EXTENSO.'</td>
					';
									
					if($this->show_total_reg)
						$this->html .= '<td style="text-align:right" class="last-line"><strong>Total:</strong> '.$this->total_regs.' registro(s)</td>';
					
					$this->html .= '
								<tr class="filters">
									<td colspan="3">
										<strong>Filtro(s) selecionado(s):</strong>
										'.$this->filters->text.'
									</td>
								</tr>
							</table>
						</div>
					';
				}
			
				//Dados do relatório
				$this->html .= '
					<div class="content">
						<table>
				';
				
				if($this->total_regs){
					//Campos
					if((!$paginate && ($page === 1)) || $paginate){
						$fields_html = '';
						$fields_html .= '<tr class="fields">';
						
						if($this->show_line_numbers)
							$fields_html .= '<td class="num">#</td>';
						
						foreach($this->fields as $field => $field_attr){
							if(!in_array($field, $this->subgroup))
								$fields_html .= '<td class="'.$field_attr['type'].'">'.$field_attr['title'].'</td>';
						}
						
						$fields_html .= '</tr>';
					}
					
					if(!sizeof($this->subgroup))
						$this->html .= $fields_html;
					
					//Valores
					for($i = $start; $i < $max; $i++){
						$group_changed = false;
						$row_count++;
						
						if(sizeof($this->subgroup)){
							$subgroup_field = $this->subgroup['field'];
							$subgroup_type = $this->subgroup['type'];
							
							if(!in_array($this->data[$i]->$subgroup_field, $aux_group)){
								$aux_group[] = $this->data[$i]->$subgroup_field;
								$subgroup_label = $this->prepare_value($this->data[$i]->$subgroup_field, $subgroup_type);
								
								$this->html .= '
									<tr class="group">
										<td colspan="'.sizeof($this->fields).'">'.$subgroup_label.'</td>
									</tr>
								'.$fields_html;
							}
						}
						
						$this->html .= '<tr class="values">';
						
						if($this->show_line_numbers)
							$this->html .= '<td class="num">'.$row_count.'</td>';
						
						foreach($this->fields as $field => $field_attr){
							if(!in_array($field, $this->subgroup)){
								$value = $this->prepare_value($this->data[$i]->$field, $field_attr['type']);
								$this->html .= '<td class="'.$field_attr['type'].'">'.$value.'</td>';
							}
						}
						
						$this->html .= '</tr>';
						$aux = $i + 1;
						
						//Contabiliza os totais do subgrupo
						if(sizeof($this->subgroup_totals)){
							foreach($this->subgroup_totals as $group_field => $group_attr){
								switch($group_attr['group_type']){
									case 'sum':
										$field = $group_field;
										$this->subgroup_totals[$group_field]['total'] += $this->data[$i]->$field;
										break;
									
									case 'inc':
										$this->subgroup_totals[$group_field]['total']++;
										break;
								}
							}
						}
						
						//Contabiliza o grupo de totais
						if(sizeof($this->total_groups)){
							foreach($this->total_groups as $group_field => $group_attr){
								switch($group_attr['group_type']){
									case 'sum':
										$field = $group_field;
										$this->total_groups[$group_field]['total'] += $this->data[$i]->$field;
										break;
									
									case 'inc':
										$this->total_groups[$group_field]['total']++;
										break;
								}
							}
						}
						
						//Totais dos subgrupos
						if(sizeof($aux_group) && !in_array($this->data[$i + 1]->$subgroup_field, $aux_group)){
							foreach($this->subgroup_totals as $group_field => $group_attr){
								$field_type = $this->fields[$group_field]['type'];
								$total = $this->prepare_value($total, $field_type);
								
								$group_colspan = $this->show_line_numbers ? sizeof($this->fields) + 1 : sizeof($this->fields);
								
								$this->html .= '
									<tr>
										<td colspan="'.$group_colspan.'" class="total">
											<strong>'.$group_attr['label'].':</strong> '.$this->prepare_value($group_attr['total'], $group_attr['type']).'
										</td>
									</tr>
								';
								
								$this->subgroup_totals[$group_field]['total'] = 0;
							}
						}
					}
				}
				else{
					//Nenhum registro
					$this->html .= '
						<tr>
							<td colspan="'.sizeof($this->fields).'" align="center">
								Nenhuma informação para exibir
							</td>
						</tr>
					';
				}
				
				//Totais
				if($this->total_regs){
					if(sizeof($this->total_groups) && ($page == $this->num_pages)){
						$group_colspan = $this->show_line_numbers ? sizeof($this->fields) + 1 : sizeof($this->fields);						
						
						$this->html .= '
							<tr>
								<td colspan="'.$group_colspan.'" style="padding:10px 0 0">
									<table class="total-group">
						';
						
						foreach($this->total_groups as $group_field => $group_attr){
							$field_type = $this->fields[$group_field]['type'];
							$total = $this->prepare_value($total, $field_type);
							
							$this->html .= '
								<tr>
									<td class="total">
										<strong>'.$group_attr['label'].':</strong> '.$this->prepare_value($group_attr['total'], $group_attr['type']).'
									</td>
								</tr>
							';
						}
						
						$this->html .= '
									</table>
								</td>
							</tr>
						';
					}
				}
				
				$this->html .= '
						</table>
					</div>
				';
				
				//Rodapé
				if($paginate){
					$this->html .= '
						<div class="footer">
							Página '.$page.' de '.$this->num_pages.'
						</div>
					';
				}
				
				$this->html .= '</div>';
			}
		}
		
		/**
		 * Formata um valor do relatório.
		 * 
		 * @param string $value Valor a ser formatado.
		 * @param string $type Tipo de formatação, que pode ser 'money', 'number', 'decimal', 'month-year' ou 'date'.
		 * @return string Valor formatado.
		 */
		private function prepare_value($value, $type){
			switch($type){
				case 'money':
					$value = 'R$ '.number_format((float)$value, 2, ',', '.');
					break;
				
				case 'number':
					$value = number_format((int)$value, 0, ',', '.');
					break;
				
				case 'decimal':
					$value = number_format((float)$value, 2, ',', '.');
					break;
					
				case 'month':
					$value = \DateTime\Date::month_name($value);
					break;
				
				case 'month-year':
					$pieces = explode('/', $value);
					$value = \DateTime\Date::month_name($pieces[0]).' de '.$pieces[1];
					break;
				
				case 'date':
					$value = !empty($value) ? \DateTime\Date::convert($value) : '---';
					break;
				
				default:
					break;
			}
			
			return $value;
		}
		
		/**
		 * Exibe o relatório.
		 * 
		 * @param boolean $paginate Define se o relatório deve efetuar paginação.
		 */
		public function display($paginate = true){
			$this->prepare_report($paginate);
			echo $this->html;
		}
		
		/**
		 * Gera um arquivo PDF para download do relatório.
		 * 
		 * @param string $filename Caminho completo do arquivo PDF a ser gravado.
		 */
		public function generate_pdf($filename = ''){
			$this->prepare_report(false);
			
			$pdf = '
				<html>
					<head>
						<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
						<title>Relatório</title>
						<style>
							body, html{
								font-family:Arial;
							}
							
							a, img, .logo{
								display:none;
							}
							
							.cabecalho{
								border-bottom:solid 2px #333;
								padding:10px 10px 0 10px;
							}
							
							.cabecalho table{
								width:100%;
								border-collapse:collapse;
							}
								.cabecalho table tr td{
									padding:1px;
								}
								.cabecalho table tr td.last-line{
									padding-bottom:5px;
								}
								.cabecalho table tr td.logo{
									width:60px;
									padding-right:10px;
								}
								.cabecalho table tr.filtros td{
									border-top:solid 1px #DDD;
									padding:5px 1px;
								}
							
							h1.titulo-relatorio{
								font-size:24px;
								margin:0;
								padding:0;
							}
							
							/*--- Conteúdo ---*/
							
							.conteudo{
								padding:5px;
							}
							
							.conteudo table{
								width:100%;
								border-collapse:collapse;
							}
								.conteudo table tr td{
									padding:4px;
								}
								.conteudo table tr.valores td{
									border-bottom:solid 1px #EEE;
								}
								.conteudo table tr td.data{
									text-align:center;
									width:100px;
								}
								.conteudo table tr td.numero, .conteudo table tr td.dinheiro{
									text-align:right;
									width:165px;
								}
								.conteudo table tr td.total{
									text-align:right;
									padding-top:10px;
								}
							
							tr.grupo td{
								background:#666;
								color:#FFF;
								font-weight:bold;
							}
							
							tr.campos td{
								background:#EEE;
								font-weight:bold;
							}
							
							tr.valores td.subvalor{
								padding-left:15px;
							}
						</style>
					</head>
					<body>
						'.$this->html.'
					</body>
				</html>
			';
			
			require_once '../../inc/dompdf/dompdf_config.inc.php';
			spl_autoload_register('DOMPDF_autoload');
			
			$dompdf = new DOMPDF();
			$dompdf->load_html($pdf);
			$dompdf->render();
			$dompdf->stream($filename.'.pdf');
		}
	}
?>