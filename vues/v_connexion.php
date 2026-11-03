<?php
$erreurConnexion = $erreurConnexion ?? '';
$messageConnexion = $messageConnexion ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Connexion | LINS</title>
	<link rel="stylesheet" href="includes/css/style.css">
</head>
<body class="page-connexion">
	<main class="connexion">
		<section class="connexion__panneau" aria-labelledby="titre-connexion">
			<p class="connexion__marque">LINS</p>
			<h1 id="titre-connexion">Connexion</h1>
			<p class="connexion__intro">Accédez à votre espace.</p>

			<?php if ($erreurConnexion !== ''): ?>
				<p class="connexion__erreur" role="alert">
					<?= htmlspecialchars($erreurConnexion, ENT_QUOTES, 'UTF-8') ?>
				</p>
			<?php endif; ?>
			<?php if ($messageConnexion !== ''): ?>
				<p class="connexion__succes" role="status">
					<?= htmlspecialchars($messageConnexion, ENT_QUOTES, 'UTF-8') ?>
				</p>
			<?php endif; ?>

			<form action="index.php?uc=auth&action=verifier" method="post">
				<div class="champ">
					<label for="email">Adresse e-mail</label>
					<input
						type="email"
						id="email"
						name="email"
						autocomplete="email"
						required
						autofocus
					>
				</div>

				<div class="champ">
					<label for="motdepasse">Mot de passe</label>
					<input
						type="password"
						id="motdepasse"
						name="motdepasse"
						autocomplete="current-password"
						required
					>
				</div>

				<button type="submit">Se connecter</button>
			</form>
		</section>
	</main>
</body>
</html>
