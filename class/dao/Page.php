<?php
	namespace DAO;
	
	/**
	 * Classe para registro de página do site.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 08/04/2014
	*/
	
	class Page extends \Database\DatabaseObject{
		const TABLE_NAME = 'sys_page';
		
		const ADMIN_PACKAGE = 'page';
		const ADMIN_MODULE = 'pages';
		
		const DESCRIPTION_TAG = 'description';
		const KEYWORDS_TAG = 'keywords';
		
		const VOTE_YES = 1,
			  VOTE_NO = 2;
		
		//Meta-tags das redes sociais
		protected static $facebook_data = array('title' => 'title', 'description' => 'description', 'type' => 'website', 'image' => '', 'url' => 'url');
		protected static $gplus_data = array('name' => 'title', 'description' => 'description', 'image' => '');
		
		protected $title;
		protected $subtitle;
		protected $slug;
		protected $text;
		protected $description;
		protected $keywords;
		protected $show;
		protected $is_faq;
		protected $url;
		
		/**
		 * @see DatabaseObject::load()
		 */
		public function load($id, $autoload = false){
			if($record = parent::load($id, $autoload)){
				$this->url = $this->get('slug');
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Carrega as perguntas e respostas do FAQ caso seja uma página do tipo.
		 * 
		 * @return array Vetor com o resultado da consulta à tabela de FAQ.
		 */
		public function get_questions(){
			global $db;
			$questions = array();
			
			if($this->get('is_faq')){
				$db->query('SELECT id, question, answer, useful_votes, useless_votes FROM sys_faq_item WHERE page_id = '.$this->get('id'));
				$questions = $db->result();
			}
			
			return $questions;
		}
		
		/**
		 * Exibe a página.
		 * 
		 * @param boolean $show_title Indica se o título da página deve ser exibido antes de seu conteúdo.
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public function display($show_title = false, $echo = true){
			global $sys_language;
			
			$faq_html = '';
			$questions = $this->get_questions();
			
			if(sizeof($questions)){
				$faq_html .= '
					<div class="faq">
						<h3>'.$sys_language->get('class_page', 'faq_list').'</h3>
				';
				
				foreach($questions as $question){
					if(\HTTP\Session::exists('voted_faq_item_'.$question->id)){
						$options = '<span class="thank-you">'.$sys_language->get('class_page', 'already_voted').'</span>';
					}
					else{
						$options = '
							<a href="#" class="yes" title="'.$sys_language->get('class_page', 'yes_useful').'" data-id="'.$question->id.'" data-vote="'.self::VOTE_YES.'">
								'.$sys_language->get('common', '_yes').'
								<span class="count">'.$question->useful_votes.'</span>
							</a>
							
							<a href="#" class="no" title="'.$sys_language->get('class_page', 'no_useful').'" data-id="'.$question->id.'" data-vote="'.self::VOTE_NO.'">
								'.$sys_language->get('common', '_no').'
								<span class="count">'.$question->useless_votes.'</span>
							</a>
						';
					}
					
					$faq_html .= '
						<div class="item">
							<p class="question">'.nl2br($question->question).'</p>
							<p class="answer">'.nl2br($question->answer).'</p>
							
							<div class="poll">
								<span class="title">'.$sys_language->get('class_page', 'was_useful').'</span>
								<div class="options">'.$options.'</div>
							</div>
						</div>
					';
				}
				
				$faq_html .= '
						<script>
							//Registra opinião
							$(".faq .poll a").click(function(){
								Ajax.load_html({a: 1, id: $(this).data("id"), vote: $(this).data("vote")}, $(this).parent());
								return false;
							});
						</script>
					</div>
				';
			}
			
			$page_title = $show_title ? '<h1>'.$this->get('title').'</h1>' : '';
			$page_subtitle = ($show_title && $this->get('subtitle')) ? '<h2>'.$this->get('subtitle').'</h2>' : '';
			
			$html = '
				<div class="page-container">
					'.$page_title.'
					'.$page_subtitle.'
					
					<div class="text">'.$this->get('text').'</div>
					'.$faq_html.'
				</div>
			';
			
			if(!$echo)
				return $html;
			
			echo $html;
		}
		
		/**
		 * @see DatabaseObject::get_before_delete_queries()
		 */
		public static function get_before_delete_queries($id){
			return array('DELETE FROM sys_faq_item WHERE page_id = '.$id);
		}
	}
?>