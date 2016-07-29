<link rel="stylesheet" href="/site/ecommerce/assets/css/order.css" />

<section id="order">
	<?php
		//Redireciona para a página de pedidos caso não exista mais pedido na sessão
		if(!HTTP\Session::exists('order_placed'))
			URL\URL::redirect('/minha-conta/pedidos');
		
		//Instancia o pedido
		$order_id = HTTP\Session::get('order_placed');
		$order = new DAO\Ecommerce\Order($order_id);
		\HTTP\Session::delete('order_placed');
		
		//Exibe os dados do pedido
		$html = '
			<h1>Pedido nº '.$order->get('code').'</h1>
			<h2>Seu pedido foi realizado com sucesso e estamos aguardando a confirmação do seu pagamento.</h2>
			<h3>Você pode acompanhar o andamento do seu pedido através da área <a href="/minha-conta/pedidos">Meus pedidos</a> em sua conta.</h3>
			
			<div class="delivery-days">
				<h4>Prazo estimado de entrega do seu pedido:</h4>
				<span>'.Formatter\String::count($order->get('delivery_days'), 'dia útil', 'dias úteis').'</span>
			</div>
			
			<div class="buttons">
				<a href="/" class="home">Página inicial</a>
				<a href="/minha-conta" class="account">Acessar sua conta</a>
			</div>
		';
		
		echo $html;
	?>
</section>