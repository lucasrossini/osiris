<link rel="stylesheet" href="/site/ecommerce/assets/css/account.css" />

<?php
	//Cabeçalho da conta
	include 'ecommerce/inc/account-header.php';
	
	//Menu da conta
	include 'ecommerce/inc/account-menu.php';
?>

<section id="wishlist">
	<h1>Lista de desejos</h1>
	
	<?php
		$client_id = $sys_user->get('id');
		
		//Ações da lista de desejos
		if(HTTP\Request::is_set('get', 'action')){
			$action = HTTP\Request::get('action');
			
			switch($action){
				case 'add': //Adiciona produto
					$product_id = (int)HTTP\Request::get('product');
					
					$db->query('SELECT COUNT(*) AS total FROM ecom_wishlist_product WHERE client_id = '.$client_id.' AND product_id = '.$product_id);
					$already_exists = ($db->result(0)->total > 0);
					
					if(!$already_exists){
						if($db->query('INSERT INTO ecom_wishlist_product (client_id, product_id, date, time) VALUES ('.$client_id.', '.$product_id.', CURDATE(), CURTIME())'))
							UI\Message::success('Produto adicionado com sucesso à sua lista de desejos!');
						else
							UI\Message::error('Falha ao adicionar produto à sua lista de desejos! Tente novamente.');
					}
					else{
						UI\Message::success('O produto já está em sua lista de desejos!');
					}
					
					break;
				
				case 'remove': //Remove produto
					$product_id = (int)HTTP\Request::get('product');
					
					if($db->query('DELETE FROM ecom_wishlist_product WHERE client_id = '.$client_id.' AND product_id = '.$product_id))
						UI\Message::success('Produto removido com sucesso da sua lista de desejos!');
					else
						UI\Message::error('Falha ao remover produto da sua lista de desejos! Tente novamente.');
					
					break;
			}
			
			\URL\URL::redirect('/minha-conta/lista-de-desejos');
		}
		
		//Carrega os produtos da lista de desejos do usuário
		$products = \DAO\Ecommerce\Product::load_all('SELECT product_id AS id FROM ecom_wishlist_product WHERE client_id = '.$client_id.' ORDER BY date DESC, time DESC');
		
		//Exibe os produtos
		$html = '<div class="products-list">';
		
		if($products['count']){
			foreach($products['results'] as $product)
				$html .= $product->get_html();
		}
		else{
			$html .= '<p class="no-results">Sua lista de desejos não possui nenhum produto!</p>';
		}
		
		$html .= '</div>';
		echo $html;
	?>
</section>

<script>
	//Botão de excluir produto
	$('#wishlist .products-list > .product').each(function(){
		var delete_link = $('<a href="/minha-conta/lista-de-desejos?action=remove&product=' + $(this).data('id') + '" class="delete">Remover produto</a>');
		$(this).prepend(delete_link);
		
		delete_link.click(function(){
			return confirm('Deseja realmente remover o produto da sua lista de desejos?');
		});
	});
</script>