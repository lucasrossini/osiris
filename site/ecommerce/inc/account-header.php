<header id="account-header">
	<div class="welcome">
		<p>Olá, <span class="name"><?php echo $sys_user->get('name') ?></span>!</p>
		<p>Seja bem-vindo(a) à sua conta.</p>
		<p class="not-me">Se não for você, <a href="/logout?next=/login">clique aqui</a>.</p>
	</div>

	<a href="/logout" class="logout">Sair</a>
</header>