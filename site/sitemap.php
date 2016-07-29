<?php
	//Páginas padrão
	$items = array(
		array('page' => '/', 'priority' => '1.00', 'changefreq' => 'daily')
	);
	
	//Adiciona as páginas fixas do site
	foreach(\HTTP\Router::get_site_routes() as $slug => $attr){
		if(!$attr['sitemap_hidden'] && !$attr['require_login']){
			$priority = $attr['sitemap_prioriry'] ? $attr['sitemap_prioriry'] : '0.80';
			$items[] = array('page' => $slug, 'priority' => $priority, 'changefreq' => 'weekly');
		}
	}
	
	//Adiciona páginas dinâmicas
	$site_class_folder = \Storage\Folder::scan('/class/dao/');
	$dao_classes = array();
	
	foreach($site_class_folder->files as $class_file)
		$dao_classes['default'][] = \Storage\File::name($class_file);
	
	//Classes E-Commerce
	if(ECOMMERCE){
		$ecommerce_class_folder = \Storage\Folder::scan('/class/dao/ecommerce/');

		foreach($ecommerce_class_folder->files as $class_file)
			$dao_classes['ecommerce'][] = \Storage\File::name($class_file);
	}
	
	$parent = new ReflectionClass('\Database\DatabaseObject');
	
	foreach($dao_classes as $area => $area_classes){
		foreach($area_classes as $dao_class){
			$class_fullname = ($area == 'ecommerce') ? '\DAO\Ecommerce\\'.$dao_class : '\DAO\\'.$dao_class;
			$reflection_class = new ReflectionClass($class_fullname);

			if($reflection_class->isSubclassOf($parent)){
				$class_name = $reflection_class->getName();
				$class_sitemap_items = array();
				$class_sitemap_data = $reflection_class->hasProperty('sitemap_data') ? $class_name::$sitemap_data : array('priority' => '0.70', 'changefreq' => 'daily', 'hidden' => false, 'sql' => '');
				
				//Páginas dinâmicas
				if(!$class_sitemap_data['hidden'] && $reflection_class->hasProperty('url') && $reflection_class->hasMethod('check_url')){
					$object_list = $class_name::load_all($class_sitemap_data['sql']);
					
					if($object_list['count']){
						foreach($object_list['results'] as $object)
							$class_sitemap_items[] = array('page' => $object->get('url'), 'priority' => $class_sitemap_data['priority'], 'changefreq' => $class_sitemap_data['changefreq']);
					}
				}
				
				//Páginas de pesquisa
				if($reflection_class->hasConstant('SEARCH_PATH'))
					$class_sitemap_items[] = array('page' => $reflection_class->getConstant('SEARCH_PATH'), 'priority' => '0.70', 'changefreq' => 'yearly');

				if(sizeof($class_sitemap_items))
					$items = array_merge($items, $class_sitemap_items);
			}
		}
	}
	
	//Exibe o sitemap.xml
	$sitemap = new \XML\Sitemap(BASE, $items);
	$sitemap->output();
?>