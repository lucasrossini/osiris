<?php
	namespace PDF;
	
	/**
	 * Classe para geração de arquivos PDF.
	 * 
	 * @package Osiris
	 * @uses mPDF
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 09/01/2014
	*/
	
	class PDF{
		const FORMAT = 'A4';
		const CREATOR = 'Osiris Framework';
		
		private $pdf;
		private $name;
		private $html;
		private $title;
		
		/**
		 * Instancia um novo arquivo PDF.
		 * 
		 * @param string $name Nome do arquivo.
		 * @param string $html Conteúdo HTML do arquivo.
		 * @param string $title Título do documento.
		 * @param boolean $landscape Define se a orientação do documento deve ser paisagem.
		 * @param array $margins Vetor com as margens do documento, com os índices 'left', 'right', 'top', 'bottom', 'header' e 'footer'.
		 */
		public function __construct($name, $html = '', $title = '', $landscape = false, $margins = array('left' => 15, 'right' => 15, 'top' => 16, 'bottom' => 16, 'header' => 9, 'footer' => 9)){
			//Inclui a biblioteca do mPDF
			require_once CORE_PATH.'/lib/pdf/mpdf/mpdf.php';
			
			//Orientação do documento
			$format = self::FORMAT;
			$orientation = 'P';
			
			if($landscape){
				$orientation = 'L';
				$format .= '-L';
			}
			
			//Instancia objeto PDF
			$this->pdf = new mPDF('', $format, 0, '', $margins['left'], $margins['right'], $margins['top'], $margins['bottom'], $margins['header'], $margins['footer'], $orientation);
			$this->name = $name;
			$this->html = $html;
			$this->title = $title;
		}
		
		/**
		 * Escreve um conteúdo HTML no arquivo.
		 * 
		 * @param string $html Conteúdo HTML.
		 */
		public function write($html){
			$this->html .= $html;
		}
		
		/**
		 * Prepara o conteúdo do arquivo para exibição ou gravação.
		 */
		private function prepare_file(){
			//Metadata
			$this->pdf->SetTitle($this->title);
			$this->pdf->SetAuthor(TITLE);
			$this->pdf->SetCreator(self::CREATOR);
			
			//Conteúdo HTML
			$this->pdf->WriteHTML($this->html);
		}
		
		/**
		 * Exibe o arquivo PDF.
		 */
		public function output(){
			$this->prepare_file();
			$this->pdf->Output($this->name.'.pdf');
			
			exit;
		}
		
		/**
		 * Salva o arquivo PDF.
		 * 
		 * @param string $path Pasta de destino.
		 */
		public function save($path = '/temp'){
			\Storage\Folder::fix_path($path);
			
			$this->prepare_file();
			$this->pdf->Output(ROOT.$path.$this->name.'.pdf', 'F');
		}
	}
?>