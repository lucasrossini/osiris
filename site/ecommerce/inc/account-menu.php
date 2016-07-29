<nav id="account-menu">
	<?php
		//Página atual
		$current_page = $sys_control->get_page(1);
		
		//Monta itens do menu
		$menu_items = array(
			'' => 'Resumo da conta',
			'pedidos' => 'Meus pedidos',
			'enderecos' => 'Meus endereços',
			'lista-de-desejos' => 'Lista de desejos',
			'editar-dados' => 'Editar meus dados'
		);
		
		//Exibe o menu
		$html = '';
		
		foreach($menu_items as $menu_item_slug => $menu_item_title){
			$current = ($current_page == $menu_item_slug) ? 'current' : '';
			$html .= '<a href="/minha-conta/'.$menu_item_slug.'" class="'.$current.'">'.$menu_item_title.'</a>';
		}
		
		echo $html;
	?>
</nav>