<?php
	//Carrega a página atual
	$page = new \DAO\Page($sys_control->get_current_page_attr('record_id'));
	
	//Exibe a página
	$page->display();
?>