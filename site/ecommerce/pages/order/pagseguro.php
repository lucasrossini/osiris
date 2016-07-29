<?php
	//Recebe retorno do PagSeguro
	$pagseguro = new \Payment\PagSeguro\PagSeguro();
	$transaction = $pagseguro->listen_notification();
	
	if(sizeof($transaction)){
		$order_id = $transaction['id'];
		$status = null;
		
		//Status da transação
		switch($transaction['status']){
			case 'confirmed':
				$status = DAO\Ecommerce\Order::STATUS_AWAITING_DISPATCH;
				break;
			
			case 'cancelled':
				$status = DAO\Ecommerce\Order::STATUS_CANCELLED;
				break;
		}
		
		if(!is_null($status)){
			//Atualiza o status do pedido
			$db->query('UPDATE '.DAO\Ecommerce\Order::TABLE_NAME.' SET status = '.$status.' WHERE id = '.$order_id);
			
			//Envia e-mail de confirmação do pagamento
			if($transaction['status'] == 'confirmed')
				\DAO\Ecommerce\Email::payment_confirmation($order_id);
		}
	}
	else{
		URL\URL::redirect('/');
	}
?>