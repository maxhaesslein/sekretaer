<?php

if( ! $sekretaer ) exit;

head_html();


$prefill_url = '';
if( ! empty($_COOKIE['sekretaer-url']) ) {
	$prefill_url = $_COOKIE['sekretaer-url'];
}

?>

<main class="login">

	<section class="login-content">

		<h2>Please log in</h2>

		<?php
		if( isset($_GET['error']) ) {
			// TODO: better error displaying
			echo '<p><strong>Error:</strong> '.$_GET['error'].'</p>';
		}
		?>


		<form id="login-form" action="<?= url('action/login') ?>" method="POST">

			<label><span style="display: inline-block; width: 60px;">URL:</span> <input type="url" name="url" placeholder="https://www.example.com" value="<?= $prefill_url ?>" autofocus style="width: 100%; max-width: 340px;" required autocomplete="username"></label>

			<span style="display: inline-block; width: 60px;"></span> <label style="display: inline-block"><input type="checkbox" name="autologin" value="true"> stay logged in <small>(this sets a cookie)</small></label>
			
			<br><span style="display: inline-block; width: 60px;"></span> <label style="display: inline-block"><input type="checkbox" name="rememberurl" value="true"<?php if( $prefill_url ) echo ' checked'; ?>> remember URL on this page <small>(this sets a cookie)</small></label>

			<br><br>

			<span style="display: inline-block; width: 60px;"></span> <button>Login</button> <span id="login-loader" class="loading hidden"></span>

			<input type="hidden" name="path" value="<?= implode('/', $sekretaer->route->request) ?>">
		
			<br><span style="display: inline-block; width: 60px;"></span> <span class="alpha-warning">this is an alpha release. things may break.</span>

		</form>
		

	</section>

</main>

<footer>
	<a href="https://github.com/maxhaesslein/sekretaer" target="_blank" rel="noopener">Sekretär</a> v.<?= $sekretaer->version() ?>
</footer>

<?php
foot_html();
