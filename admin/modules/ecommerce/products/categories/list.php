<style>
	span.sub{
		padding-left: 20px;
	}
</style>

<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Lista os registros
	$sql = 'SELECT c.id,
			(CASE WHEN c.parent_id IS NOT NULL THEN CONCAT("<span class=\'sub\'>", c.name, "</span>") ELSE c.name END) AS name,
			(CASE WHEN c.parent_id IS NULL THEN CONCAT("'.DAO\Ecommerce\Category::BASE_PATH.'", c.slug) ELSE CONCAT("'.DAO\Ecommerce\Category::BASE_PATH.'", (SELECT pc.slug FROM ecom_category pc WHERE c.parent_id = pc.id), "/", c.slug) END) AS url,
			(SELECT COUNT(*) FROM ecom_product_category pc WHERE pc.category_id = c.id) AS total_products,
			(CASE WHEN c.visible = 1 THEN "'.$sys_language->get('common', '_yes').'" ELSE "'.$sys_language->get('common', '_no').'" END) AS visible
			FROM '.DAO\Ecommerce\Category::TABLE_NAME.' c
			WHERE '.search_where_clause(array('c.name'));
	
	//Tabela
	$columns = array(
		'name' => array('name' => $module_language->get('form', 'name')),
		'url' => array('name' => 'URL'),
		'total_products' => array('name' => $module_language->get('list', 'products')),
		'visible' => array('name' => $module_language->get('form', 'visible'))
	);
	
	$table = new \Util\Table('records', $sql, $columns);
	$table->sort('url');
	$table->display();
?>