<style>
	.insert-record{ display: none; }
	
	/*-- Fitros --*/
	
	#form_filter .label-title{
		display: none;
	}
	#form_filter > h3{
		font-weight: bold;
		margin-bottom: 10px;
	}
	#form_filter > .fields{
		margin-bottom: 20px;
	}
		#form_filter > .fields > .label{
			padding: 0;
			background: none;
			border: none;
		}
		#form_filter > .fields > .label, #form_filter > .fields > button{
			float: left;
			margin-right: 20px;
		}
		#form_filter > .fields > .clear-filters{
			display: inline-block;
			margin-top: 8px;
		}
</style>

<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Status
	$order_statuses = array(
		'' => $sys_language->get('common', 'select'),
		DAO\Ecommerce\Order::STATUS_AWAITING_PAYMENT => DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_AWAITING_PAYMENT),
		DAO\Ecommerce\Order::STATUS_AWAITING_DISPATCH => DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_AWAITING_DISPATCH),
		DAO\Ecommerce\Order::STATUS_DELIVERED => DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_DELIVERED),
		DAO\Ecommerce\Order::STATUS_CANCELLED => DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_CANCELLED)
	);
	
	//Filtros
	$order_statuses_filter = $order_statuses;
	$order_statuses_filter[''] = $module_language->get('form', 'status');
	
	$filter_form = new Form\Form('form_filter', 'get');
	$filter_form->add_html('<h3>'.$module_language->get('list', 'filter_orders').'</h3><div class="fields clearfix">');
	$filter_form->add_field(new \Form\Select('status', $module_language->get('form', 'status'), '', array(), $order_statuses_filter));
	$filter_form->add_field(new \Form\Date('date_from', $module_language->get('list', 'date_from'), '', array('placeholder' => $module_language->get('list', 'date_from'))));
	$filter_form->add_field(new \Form\Date('date_to', $module_language->get('list', 'date_to'), '', array('placeholder' => $module_language->get('list', 'date_to'))));
	$filter_form->add_field(new \Form\Button('submit_button', 'OK'));
	
	if(\HTTP\Request::get('form_filter_submit'))
		$filter_form->add_html('<a href="admin/clients/orders/list" class="clear-filters">[Limpar filtros]</a>');
	
	$filter_form->add_html('</div>');
	$filter_form->display();
?>

<form method="post">
	<?php
		//Altera status de pedidos
		if(\HTTP\Request::is_set('post', 'status')){
			$records = \HTTP\Request::post('record');
			$status = \HTTP\Request::post('status');
			$tracking_set_clause = \HTTP\Request::is_set('post', 'tracking_code') ? ', tracking_code = "'.\HTTP\Request::post('tracking_code').'"' : '';
			$result = false;
			
			switch($status){
				case DAO\Ecommerce\Order::STATUS_AWAITING_DISPATCH:
					$status_date_clause = ', payment_datetime = NOW(), dispatch_datetime = NULL';
					break;

				case DAO\Ecommerce\Order::STATUS_DELIVERED:
					$status_date_clause = ', dispatch_datetime = NOW()';
					break;
				
				default:
					$status_date_clause = ', payment_datetime = NULL, dispatch_datetime = NULL';
			}
			
			if(is_array($records) && sizeof($records) && ((string)$status !== '') && array_key_exists($status, $order_statuses))
				$result = $db->query('UPDATE '.DAO\Ecommerce\Order::TABLE_NAME.' SET status = '.$status.$tracking_set_clause.$status_date_clause.' WHERE id IN ('.implode(',', $records).')');
			
			if($result){
				\UI\Message::success($module_language->get('list', 'change_status_success'));
				
				//Envia e-mail
				switch($status){
					case DAO\Ecommerce\Order::STATUS_AWAITING_DISPATCH:
						foreach($records as $order_id)
							\DAO\Ecommerce\Email::payment_confirmation($order_id);
						
						break;
					
					case DAO\Ecommerce\Order::STATUS_DELIVERED:
						foreach($records as $order_id)
							\DAO\Ecommerce\Email::dispatch_confirmation($order_id);
						
						break;
				}
			}
			else{
				\UI\Message::error($module_language->get('list', 'change_status_error'));
			}
			
			URL\URL::reload();
		}

		//Lista os registros
		$date_from = \HTTP\Request::get('date_from') ? DateTime\Date::convert(\HTTP\Request::get('date_from')) : '0000-00-00';
		$date_to = \HTTP\Request::get('date_to') ? DateTime\Date::convert(\HTTP\Request::get('date_to')) : date('Y-m-d');
		
		$sql = 'SELECT o.id, CONCAT(o.date, " ", o.time) AS datetime, o.total, o.tracking_code, c.name AS client,
				(CASE
					WHEN (o.status = '.DAO\Ecommerce\Order::STATUS_AWAITING_PAYMENT.') THEN
						"<span class=\'status-0\'>'.DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_AWAITING_PAYMENT).'</span>"
					ELSE
						CASE WHEN (o.status = '.DAO\Ecommerce\Order::STATUS_AWAITING_DISPATCH.') THEN
							"<span class=\'status-1\'>'.DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_AWAITING_DISPATCH).'</span>"
						ELSE
							CASE WHEN (o.status = '.DAO\Ecommerce\Order::STATUS_DELIVERED.') THEN
								"<span class=\'status-2\'>'.DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_DELIVERED).'</span>"
							ELSE
								CASE WHEN (o.status = '.DAO\Ecommerce\Order::STATUS_CANCELLED.') THEN
									"<span class=\'status-3\'>'.DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_CANCELLED).'</span>"
								ELSE
									""
								END
							END
						END
					END) AS status,
				(SELECT SUM(op.quantity) FROM ecom_order_product op WHERE op.order_id = o.id) AS items_count
				FROM '.DAO\Ecommerce\Order::TABLE_NAME.' o, '.\DAO\Ecommerce\Client::TABLE_NAME.' c
				WHERE o.client_id = c.id AND (o.date BETWEEN "'.$date_from.'" AND "'.$date_to.'") AND '.filter_where_clause(array('o.status' => 'status')).' AND '.search_where_clause(array('c.name', 'c.email', 'c.id', 'c.cpf', 'o.id'));

		//Tabela
		$columns = array(
			'id' => array('name' => $module_language->get('list', 'code')),
			'client' => array('name' => $module_language->get('form', 'client')),
			'items_count' => array('name' => $module_language->get('list', 'items_count')),
			'total' => array('name' => $module_language->get('form', 'total'), 'type' => 'money'),
			'datetime' => array('name' => $module_language->get('form', 'datetime'), 'type' => 'date'),
			'status' => array('name' => $module_language->get('form', 'status')),
			'tracking_code' => array('name' => $module_language->get('form', 'tracking_code'))
		);

		$table = new \Util\Table('records', $sql, $columns, 10, true, true, false, false);
		$table->sort('datetime', Util\Table::SORT_DESC);
		$table->display();
		
		//Alterar status
		echo '
			<label class="change-status">
				<strong>'.$module_language->get('list', 'change_status').'</strong>
				<select name="status">'.Form\Select::array2options($order_statuses).'</select>
				<button type="submit">OK</button>
			</label>
		';
	?>
</form>