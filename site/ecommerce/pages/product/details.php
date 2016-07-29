<link rel="stylesheet" href="/site/ecommerce/assets/css/product.css" />
<link rel="stylesheet" href="/site/ecommerce/assets/css/shipping.css" />

<section id="product-details">
	<?php
		//Carrega os recursos necessários
		$sys_assets->load('css', 'app/assets/js/jquery/plugins/fancybox/jquery.fancybox.css');
		$sys_assets->load('js', 'app/assets/js/jquery/plugins/fancybox/jquery.fancybox.pack.js', array('charset' => 'ISO-8859-1'));
		$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.maskedinput.min.js');
		$sys_assets->load('js', 'site/ecommerce/assets/js/jquery.zoom.min.js');
		
		//Tamanho das imagens
		define('MAIN_IMAGE_SIZE', 400);
		define('GALLERY_IMAGE_SIZE', 100);
		
		//Carrega os dados do produto selecionado
		$product = new \DAO\Ecommerce\Product($sys_control->get_current_page_attr('record_id'));
		
		//Ações do produto
		if(\HTTP\Request::is_set('get', 'action')){
			$action = \HTTP\Request::get('action');

			switch($action){
				case 'calculate_shipping': //Calcula o frete
					$zip_code = \HTTP\Request::post('zip_code');
					$product->calculate_shipping($zip_code);
					
					break;
			}
			
			URL\URL::redirect($product->get('url'));
		}
		
		//Registra visualização do produto pelo usuário logado
		$product->log_view();
		
		//Carrega as categorias que o produto pertence
		$categories = $product->get_categories();
		$categories_html = '<ul>';
		
		foreach($categories as $category){
			$category_obj = $category['category'];
			$categories_html .= '<li><a href="'.$category_obj->get('url').'">'.$category_obj->get('name').'</a>';
			
			if(sizeof($category['subcategories'])){
				$categories_html .= '<ul>';
				
				foreach($category['subcategories'] as $subcategory)
					$categories_html .= '<li><a href="'.$subcategory->get('url').'">'.$subcategory->get('name').'</a></li>';
				
				$categories_html .= '</ul>';
			}
			
			$categories_html .= '</li>';
		}
		
		$categories_html .= '</ul>';
		
		//Exibe as informações do produto
		if($product->get('promotional_price')){
			$price_html = '
				<span class="original">
					<del>'.$product->get('price')->formatted.'</del>
					<span class="save">(Economize '.$product->get('discount_value')->formatted.')</span>
				</span>
				
				<ins class="promotional">'.$product->get('promotional_price')->formatted.'</ins>
			';
		}
		else{
			$price_html = '<span class="default">'.$product->get('price')->formatted.'</span>';
		}
		
		if($product->get('free_shipping'))
			$price_html .= '<span class="free-shipping">Frete grátis!</span>';
		
		//Variações
		$variations_html = '';
		$variations = $product->get_variations();
		
		if(sizeof($variations)){
			$variations_html = '<div class="variations-box">';
			$variation_options = array();
			$already_variations = array();
			
			foreach($variations as $variation){
				if(!in_array($variation->get('variation_type')->get('id'), $already_variations)){
					if(sizeof($already_variations))
						$variations_html .= Util\ArrayUtil::listify($variation_options);
					
					$variations_html .= '<h3>'.$variation->get('variation_type')->get('name').'</h3>';
					$already_variations[] = $variation->get('variation_type')->get('id');
					$variation_options = array();
				}
				
				if($variation->get('variation_stock')){
					$out_of_stock = '';
					$in_stock = 'true';
				}
				else{
					$out_of_stock = 'out-of-stock';
					$in_stock = 'false';
				}
				
				$variation_options[] = '<a href="#" class="variation '.$out_of_stock.'" data-id="'.$variation->get('id').'" data-in_stock="'.$in_stock.'">'.$variation->get('variation').'</a>';
			}
			
			$variations_html .= Util\ArrayUtil::listify($variation_options).'</div>';
		}
		
		//Cálculo de frete
		$product_shipping = $product->get('shipping');
		
		$shipping_box_html = '
			<form method="post" action="'.$product->get('url').'?action=calculate_shipping" class="shipping-box">
				<h3>Consulte o frete e o prazo de entrega</h3>

				<input type="text" id="zip_code" name="zip_code" value="'.$product_shipping['zip_code'].'" placeholder="Digite seu CEP" />
				<button type="submit">Calcular</button>

				<a href="http://www.buscacep.correios.com.br" target="_blank" class="find-cep">Não sei meu CEP</a>
		';

		if(sizeof($product_shipping)){
			$shipping_box_html .= '<div class="methods">';
			
			foreach($product_shipping['methods'] as $shipping_method_id => $shipping_attr){
				$shipping_price_html = ($shipping_method_id != DAO\Ecommerce\ShippingMethod::FREE_SHIPPING_ID) ? '<span class="price">'.Formatter\Number::money($shipping_attr['price']).'</span>' : '';

				$shipping_box_html .= '
					<div class="method">
						<span class="name">'.$shipping_attr['name'].'</span>
						'.$shipping_price_html.'
						<span class="delivery">'.Formatter\String::count($shipping_attr['delivery_days'], 'dia útil', 'dias úteis').'</span>
					</div>
				';
			}
			
			$shipping_box_html .= '</div>';
		}
		
		$html .= '</form>';
		
		//Galeria de fotos
		$gallery_html = '';
		$photos = $product->get_photos();
		
		if(sizeof($photos)){
			array_unshift($photos, array('file' => $product->get('image'), 'subtitle' => $product->get('name'), 'current' => true));
			$gallery_html = '<div class="gallery">';
			
			foreach($photos as $photo){
				$photo_obj = new \Media\Image($photo['file']);
				$new_main_dimensions = $photo_obj->get_resize_dimensions(MAIN_IMAGE_SIZE, MAIN_IMAGE_SIZE);
				$new_gallery_dimensions = $photo_obj->get_resize_dimensions(GALLERY_IMAGE_SIZE, GALLERY_IMAGE_SIZE);
				
				$current = $photo['current'] ? 'current' : '';
				$gallery_html .= '<a href="'.$photo['file'].'" data-thumb="'.Media\Image::thumb($photo['file'], MAIN_IMAGE_SIZE, MAIN_IMAGE_SIZE).'" data-width="'.$new_main_dimensions['width'].'" data-height="'.$new_main_dimensions['height'].'" class="'.$current.'" style="width: '.GALLERY_IMAGE_SIZE.'px; height: '.GALLERY_IMAGE_SIZE.'px"><img data-original="'.Media\Image::thumb($photo['file'], GALLERY_IMAGE_SIZE, GALLERY_IMAGE_SIZE).'" width="'.$new_gallery_dimensions['width'].'" height="'.$new_gallery_dimensions['height'].'" alt="'.$photo['subtitle'].'" class="lazy" /></a>';
			}
			
			$gallery_html .= '</div>';
		}
		
		//Botão de compra
		$buy_button_html = !$product->is_out_of_stock() ? '<button type="submit" class="buy">Comprar</button>' : '<button type="button" class="out-of-stock">Esgotado</button>';
		
		echo '
			<header>
				<h1>'.$product->get('name').'</h1>
				<span class="code">Código '.$product->get('code').'</span>
			</header>
			
			<div class="info">
				<div class="images">
					<a href="'.$product->get('image').'" title="'.$product->get('name').'" class="main-image" style="width: '.MAIN_IMAGE_SIZE.'px; height: '.MAIN_IMAGE_SIZE.'px">'.$product->get_img_tag(MAIN_IMAGE_SIZE, MAIN_IMAGE_SIZE).'</a>
					'.$gallery_html.'
					<p class="image-tip">Passe o mouse sobre a imagem para ampliar</p>
				</div>
				
				<div class="details">
					<div class="price">'.$price_html.'</div>
					'.$variations_html.'

					<div class="buy-container">
						<form method="get" action="/carrinho">
							<input type="hidden" name="action" value="add" />
							<input type="hidden" name="product" value="'.$product->get('id').'" />
							<input type="hidden" name="variation" value="" />
							'.$buy_button_html.'
						</form>

						<a href="/minha-conta/lista-de-desejos?action=add&product='.$product->get('id').'" class="wishlist">Adicionar à lista de desejos</a>

						<p class="variation-warning">Por favor, selecione uma opção para continuar</p>
						<p class="stock-warning">Produto indisponível</p>
					</div>

					<div class="categories">
						<h3>Veja mais em</h3>
						'.$categories_html.'
					</div>
					
					'.$shipping_box_html.'
				</div>
			</div>
			
			<div class="share">
				<span class="btn facebook">'.\Social\Facebook\Facebook::like_button().'</span>
				<span class="btn twitter">'.\Social\Twitter\Twitter::tweet_button().'</span>
				<span class="btn gplus">'.\Google\GooglePlus::plusone_button().'</span>
			</div>
			
			<div class="description">
				<h2>Descrição do produto</h2>
				'.$product->get('description').'
			</div>
		';
		
		//Tags do produto
		$tags = $product->get_tags();
		
		if(sizeof($tags)){
			$html = '
				<nav class="tags">
					<h3>Tags do produto</h3>
			';
			
			foreach($tags as $tag)
				$html .= '<a href="'.$tag->get('url').'" title="Ver produtos com a tag &quot;'.$tag->get('tag').'&quot;" class="tag">'.$tag->get('tag').'</a>';
			
			$html .= '</nav>';
			echo $html;
		}
		
		//Outros produtos da categoria
		$html = '';
		$other_products = \DAO\Ecommerce\Product::load_all('SELECT DISTINCT p.id AS id FROM ecom_product_category pc, ecom_product p WHERE pc.product_id = p.id AND p.visible = 1 AND p.id != '.$product->get('id').' AND pc.category_id IN (SELECT pc2.category_id FROM ecom_product_category pc2 WHERE pc2.product_id = '.$product->get('id').') ORDER BY RAND()', 0, 4);
		
		if($other_products['count']){
			$html .= '
				<div class="other-products">
					<h4>Confira também</h4>
					<div class="products-list">
			';
			
			foreach($other_products['results'] as $other_product)
				$html .= $other_product->get_html();
			
			$html .= '
					</div>
				</div>
			';
			
			echo $html;
		}
	?>
</section>

<script>
	$(document).ready(function(){
		//Zoom nas fotos
		$('#product-details .info > .images > .main-image').zoom({url: '<?php echo $product->get('image') ?>'}).fancybox();
		
		//Galeria de fotos
		$('#product-details .info > .images > .gallery > a').click(function(){
			if(!$(this).hasClass('current')){
				var main_image = $('#product-details .info > .images > .main-image');

				$(this).siblings().removeClass('current').end().addClass('current');
				main_image.attr({href: $(this).attr('href'), title: $(this).find('img').attr('alt')}).find('img.lazy').attr({src: 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==', width: $(this).data('width'), height: $(this).data('height')}).attr('data-original', $(this).data('thumb')).lazyload({placeholder: 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='}).end().trigger('zoom.destroy').zoom({url: $(this).attr('href')}).fancybox();
			}
			
			return false;
		});
		
		//Frete
		$('#zip_code').mask('99999-999');
		
		<?php
			//Script de variações
			if(sizeof($variations)){
				echo '
					//Variações
					$("#product-details .variations-box .variation").click(function(){
						var buy_container = $("#product-details .buy-container");
						
						buy_container.find(".variation-warning").hide();
						$("#product-details .variations-box .variation").removeClass("selected");
						$(this).addClass("selected");
						
						if(!$(this).data("in_stock")){
							buy_container.find(".stock-warning").show();
							buy_container.find(".buy").attr("disabled", true);
							buy_container.find("input[name=\'variation\']").val("");
						}
						else{
							buy_container.find(".stock-warning").hide();
							buy_container.find(".buy").removeAttr("disabled");
							buy_container.find("input[name=\'variation\']").val($(this).data("id"));
						}
						
						return false;
					});
					
					//Comprar
					$("#product-details .buy-container form").submit(function(){
						if(!$(this).parent().find("input[name=\'variation\']").val()){
							$(this).parent().find(".variation-warning").show();
							return false;
						}
						
						$(this).find(".buy").attr("disabled", true);
						$(this).parent().find(".variation-warning").hide();
					});
				';
			}
		?>
	});
</script>