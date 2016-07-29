<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Captura os parâmetros
	$id = (int)\HTTP\Request::get('id');
	$mode = strtolower(\HTTP\Request::get('mode'));
	
	//Campos
	$fields = array(
		'name' => array(
			'save' => true,
			'validation' => array('is_empty')
		),
		'slug' => array(
			'save' => true,
			'type' => 'slug[name]'
		),
		'visible' => array(
			'save' => true,
			'type' => 'boolean'
		),
		'price' => array(
			'save' => true,
			'type' => 'float',
			'validation' => array('is_empty', 'is_decimal')
		),
		'promotional_price' => array(
			'save' => true,
			'type' => 'float',
			'validation' => array('is_decimal')
		),
		'description' => array(
			'save' => true,
			'type' => 'editor',
			'validation' => array('is_empty')
		),
		'tag_id' => array(
			'save' => true,
			'related' => true,
			'is_array' => true,
			'table' => 'ecom_product_tag'
		),
		'free_shipping' => array(
			'save' => true,
			'type' => 'boolean'
		),
		'categories' => array(
			'validation' => array('is_empty', 'has_repeated_entries')
		),
		'category_id' => array(
			'save' => true,
			'related' => true,
			'is_array' => true,
			'table' => 'ecom_product_category'
		),
		'sku' => array(
			'save' => true,
			'validation' => array('already_exists')
		),
		'stock' => array(
			'save' => true,
			'type' => 'int',
			'validation' => array('is_number')
		),
		'order_limit' => array(
			'save' => true,
			'type' => 'int',
			'validation' => array('is_number')
		),
		'weight' => array(
			'save' => true,
			'type' => 'float',
			'validation' => array('is_empty', 'is_decimal')
		),
		'length' => array(
			'save' => true,
			'type' => 'float',
			'validation' => array('is_empty', 'is_decimal')
		),
		'width' => array(
			'save' => true,
			'type' => 'float',
			'validation' => array('is_empty', 'is_decimal')
		),
		'height' => array(
			'save' => true,
			'type' => 'float',
			'validation' => array('is_empty', 'is_decimal')
		),
		'variation_type_id' => array(
			'save' => true,
			'related' => true,
			'is_array' => true,
			'table' => 'ecom_product_variation',
			'validation' => array('is_empty', 'is_valid_option', 'ignore_disabled')
		),
		'variation' => array(
			'save' => true,
			'related' => true,
			'is_array' => true,
			'table' => 'ecom_product_variation',
			'validation' => array('is_empty', 'ignore_disabled')
		),
		'variation_sku' => array(
			'save' => true,
			'related' => true,
			'is_array' => true,
			'table' => 'ecom_product_variation',
			'validation' => array('ignore_disabled')
		),
		'variation_stock' => array(
			'save' => true,
			'related' => true,
			'type' => 'int',
			'is_array' => true,
			'table' => 'ecom_product_variation',
			'validation' => array('is_empty', 'is_number', 'ignore_disabled'),
		),
		'image' => array(
			'save' => true,
			'validation' => array('is_file')
		),
		'file' => array(
			'save' => true,
			'related' => true,
			'is_array' => true,
			'table' => 'ecom_product_photo',
			'validation' => array('is_file')
		),
		'subtitle' => array(
			'save' => true,
			'related' => true,
			'is_array' => true,
			'table' => 'ecom_product_photo'
		),
		'date' => array(
			'save' => true,
			'type' => 'curdate'
		),
		'time' => array(
			'save' => true,
			'type' => 'curtime'
		)
	);
	
	//Relacionamentos
	$relationships = array(
		'ecom_product_tag' => array(
			'foreign_key' => 'product_id',
			'mode' => 'edit'
		),
		'ecom_product_category' => array(
			'foreign_key' => 'product_id',
			'mode' => 'edit'
		),
		'ecom_product_photo' => array(
			'foreign_key' => 'product_id',
			'mode' => 'edit'
		),
		'ecom_product_variation' => array(
			'foreign_key' => 'product_id',
			'mode' => 'edit',
			'delete_before' => !HTTP\Request::post('has_variation')
		)
	);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode($mode);
	$form->set_database_options(DAO\Ecommerce\Product::TABLE_NAME, $fields, $id, $relationships, '\DAO\Ecommerce\Product');
	
	//Categorias
	$category_options = array('' => $sys_language->get('common', 'select'));
	$db->query('SELECT id, name FROM '.\DAO\Ecommerce\Category::TABLE_NAME.' WHERE parent_id IS NULL ORDER BY name');
	$categories = $db->result();
	
	foreach($categories as $category){
		$category_options[$category->id] = $category->name;
		
		$db->query('SELECT id, name FROM '.\DAO\Ecommerce\Category::TABLE_NAME.' WHERE parent_id = '.$category->id.' ORDER BY name');
		$subcategories = $db->result();
		
		foreach($subcategories as $subcategory)
			$category_options[$subcategory->id] = $category->name.' &rsaquo; '.$subcategory->name;
	}
	
	$category_items = array('category_id' => array('label' => $module_language->get('form', 'category'), 'options' => $category_options));
	
	//Cria os campos do formulário
	$form->init_tab('tab-general', $module_language->get('tabs', 'general')); //Aba geral
	$form->add_field(new \Form\TextInput('name', $module_language->get('form', 'name')));
	
	$form->add_html('<div class="inline-labels grid-4">');
	$form->add_field(new \Form\Money('price', $module_language->get('form', 'price')));
	$form->add_field(new \Form\Money('promotional_price', $module_language->get('form', 'promotional_price')));
	$form->add_html('</div>');
	
	$form->add_field(new \Form\Editor('description', $module_language->get('form', 'description')));
	$form->add_field(new \Form\SelectGroup('categories', $module_language->get('form', 'product_categories'), array(), array(), $category_items));
	$form->add_field(new \Form\Autocomplete('tag_id', 'Tags', array(), array(), 'app/core/util/ajax/handler?page=autocomplete&a=1', false, true), $module_language->get('form', 'tags_tip'));
	
	$form->add_html('<div class="inline-labels grid-4">');
	$form->add_field(new \Form\Checkbox('visible', $module_language->get('form', 'visible'), 1, array(), true));
	$form->add_field(new \Form\Checkbox('free_shipping', $module_language->get('form', 'free_shipping'), 1));
	$form->add_html('</div>');
	$form->end_tab();
	
	$form->init_tab('tab-stock', $module_language->get('tabs', 'stock')); //Aba estoque
	$form->add_html('<div class="inline-labels grid-3">');
	$form->add_field(new \Form\TextInput('sku', 'SKU'), $module_language->get('form', 'sku_tip'));
	$form->add_field(new \Form\Number('stock', $module_language->get('form', 'stock')));
	$form->add_field(new \Form\Number('order_limit', $module_language->get('form', 'order_limit')));
	$form->add_html('</div>');
	
	$form->add_html('<div class="inline-labels grid-4">');
	$form->add_field(new \Form\Number('weight', $module_language->get('form', 'weight'), '', array(), 'g'));
	$form->add_field(new \Form\Number('length', $module_language->get('form', 'length'), '', array(), 'cm'));
	$form->add_field(new \Form\Number('width', $module_language->get('form', 'width'), '', array(), 'cm'));
	$form->add_field(new \Form\Number('height', $module_language->get('form', 'height'), '', array(), 'cm'));
	$form->add_html('</div>');
	$form->end_tab();
	
	$form->init_tab('tab-variations', $module_language->get('tabs', 'variations')); //Aba variações do produto
	$form->add_field(new \Form\Checkbox('has_variation', $module_language->get('form', 'has_variation'), 1, array(), sizeof($form->get('variation_type_id')) ? true : false));
	
	$variations_items = array(
		new \Form\Select('variation_type_id[]', $module_language->get('form', 'variation_type'), '', array(), Form\Select::load_options(\DAO\Ecommerce\VariationType::TABLE_NAME, '[name]', 'TRUE', 'name')),
		new \Form\TextInput('variation[]', $module_language->get('form', 'variation')),
		new \Form\TextInput('variation_sku[]', 'SKU'),
		new \Form\Number('variation_stock[]', $module_language->get('form', 'stock'))
	);
	
	$form->add_field(new \Form\MixedGroup('variations', 'Variações', array(), array(), $variations_items));
	$form->end_tab();
	
	$form->init_tab('tab-images', $module_language->get('tabs', 'images')); //Aba imagens
	$form->add_field(new \Form\Image('image', $module_language->get('form', 'main_image'), '', array(), '/uploads/ecommerce/images/products/', array('width' => 1000, 'height' => 1000)));
	$form->add_field(new \Form\Gallery('file', $module_language->get('form', 'gallery'), array(), array(), '/uploads/ecommerce/images/products/gallery/', 'subtitle', true, 3), $module_language->get('form', 'gallery_tip'));
	$form->end_tab();
	
	$form->add_html('<div class="button-container">');
	$form->add_field(new \Form\Button('submit_button'));
	$form->add_field(new \Form\Button('cancel_button', $sys_language->get('common', 'cancel'), '', array(), 'button'));
	$form->add_html('</div>');
	
	//Valida o formulário
	$form->validate();
	
	//Detecta alterações no formulário
	$form->detect_changes();
	
	//Exibe o formulário
	$form->display();
	
	//Apaga um registro
	$form->delete();
	
	//Trata formulário após o envio
	$form->process();
?>

<script>
	//Controle de variações
	function check_has_variation(){
		if($('#has_variation').is(':checked'))
			$('#variations-mixedgroup .inline-labels').find('input, select').removeAttr('disabled').end().find('.add').show();
		else
			$('#variations-mixedgroup .inline-labels').find('input, select').val('').attr('disabled', true).end().find('.add').hide();
	}
	
	$('#has_variation').click(function(){
		check_has_variation();
	});
	
	check_has_variation();
</script>