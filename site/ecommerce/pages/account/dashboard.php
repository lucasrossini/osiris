<link rel="stylesheet" href="/site/ecommerce/assets/css/account.css" />

<?php
	//Cabeçalho da conta
	include 'ecommerce/inc/account-header.php';
	
	//Menu da conta
	include 'ecommerce/inc/account-menu.php';
?>

<section id="account-dashboard">
	<h1>Minha conta</h1>
	
	<?php
		$html = '';
		$client_id = $sys_user->get('id');
		$client = new \DAO\Ecommerce\Client($client_id);
		
		//Carrega os últimos pedidos (30 dias)
		$orders = \DAO\Ecommerce\Order::load_all('SELECT id FROM '.\DAO\Ecommerce\Order::TABLE_NAME.' WHERE client_id = '.$client_id.' AND DATEDIFF("'.date('Y-m-d').'", date) <= '.\DAO\Ecommerce\Order::LAST_ORDERS_INTERVAL.' ORDER BY date DESC, time DESC');
		
		$html .= '
			<div id="orders-list" class="box">
				<h2><a href="/minha-conta/pedidos">Últimos pedidos</a></h2>
		';
		
		if($orders['count']){
			$html .= '
				<table class="orders records">
					<tr>
						<th>Código</th>
						<th>Valor total</th>
						<th>Data/Hora</th>
						<th>Situação</th>
						<th>Opções</th>
					</tr>
			';
			
			foreach($orders['results'] as $order){
				$html .= '
					<tr>
						<td class="code">'.$order->get('code').'</td>
						<td class="total">'.$order->get('total')->formatted.'</td>
						<td class="datetime">'.$order->get('date')->formatted.', '.$order->get('time')->formatted.'</td>
						<td class="status s'.(int)$order->get('status').'">'.\DAO\Ecommerce\Order::get_status_name($order->get('status')).'</td>
						<td class="options"><a href="/minha-conta/pedidos?id='.$order->get('id').'">Ver detalhes</a></td>
					</tr>
				';
			}
			
			$html .= '</table>';
		}
		else{
			$html .= '<p class="no-results">Nenhum pedido realizado recentemente</p>';
		}
		
		$html .= '</div>';
		
		//Produtos visualizados recentemente
		$viewed_products = \DAO\Ecommerce\Product::load_all('SELECT DISTINCT product_id AS id FROM ecom_product_view WHERE client_id = '.$client_id.' ORDER BY date DESC, time DESC', 0, 5);
		
		$html .= '
			<div id="viewed-products" class="box">
				<h2>Produtos visualizados recentemente</h2>
				<div class="products-list">
		';
		
		if($viewed_products['count']){
			foreach($viewed_products['results'] as $product)
				$html .= $product->get_html();
		}
		else{
			$html .= '<p class="no-results">Nenhum produto visualizado</p>';
		}
		
		$html .= '
				</div>
			</div>
		';

		//Carrega as buscas mais realizadas
		$searches = $client->get_top_searches();
		
		$html .= '
			<div id="searches" class="box">
				<h2>Buscas mais realizadas</h2>
		';
		
		if(sizeof($searches)){
			foreach($searches as $search)
				$html .= '<a href="/pesquisa?q='.$search['query'].'" class="term">'.$search['query'].' ('.$search['count'].')</a>';
		}
		else{
			$html .= '<p class="no-results">Nenhuma busca realizada</p>';
		}
		
		$html .= '</div>';
		
		//Exibe o conteúdo
		echo $html;
	?>
</section>