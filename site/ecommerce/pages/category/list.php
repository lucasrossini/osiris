<link rel="stylesheet" href="/site/ecommerce/assets/css/categories.css" />

<section id="categories-list">
	<h1>Categorias</h1>
	
	<?php
		//Carrega as categorias visíveis
		$categories = DAO\Ecommerce\Category::load_all('SELECT id FROM ecom_category WHERE visible = 1 AND parent_id IS NULL ORDER BY name');
		$html = $letter = '';

		foreach($categories['results'] as $category){
			//Letra da categoria
			$category_letter = strtoupper(substr($category->get('name'), 0, 1));

			if($category_letter != $letter){
				$letter = $category_letter;

				if(!empty($html))
					$html .= '</ul>';

				$html .= '
					<h3 class="letter">'.$letter.'</h3>
					<ul>
				';
			}
			
			//Total de produtos da categoria
			$html .= '
				<li>
					<a href="'.$category->get('url').'" class="name">'.$category->get('name').'</a>
					<span class="count">'.\Formatter\String::count($category->get_products_count(), 'produto', 'produtos').'</span>
			';

			//Subcategorias
			$subcategories = DAO\Ecommerce\Subcategory::load_all('SELECT id FROM ecom_category WHERE visible = 1 AND parent_id = '.$category->get('id').' ORDER BY name');

			if($subcategories['count']){
				$html .= '<ul>';

				foreach($subcategories['results'] as $subcategory){
					$html .= '
						<li>
							<a href="'.$subcategory->get('url').'" class="name">'.$subcategory->get('name').'</a>
							<span class="count">'.\Formatter\String::count($subcategory->get_products_count(), 'produto', 'produtos').'</span>
						</li>
					';
				}

				$html .= '</ul>';
			}

			$html .= '</li>';
		}

		echo $html.'</ul>';
	?>
</section>