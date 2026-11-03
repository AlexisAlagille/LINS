<?php
$erreurConnexion = $erreurConnexion ?? '';
$messageConnexion = $messageConnexion ?? '';
$inscriptionReussie = $inscriptionReussie ?? false;
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
	<?php require __DIR__ . '/v_header.php'; ?>
	<main class="connexion">
		<section class="connexion__panneau" aria-labelledby="titre-connexion">
			<p class="connexion__marque">LINS</p>
			<h1 id="titre-connexion">Connexion</h1>
			<p class="connexion__intro">Accédez à votre espace.</p>

			<?php if ($inscriptionReussie): ?>
				<p class="connexion__succes" role="status">
					Votre compte a été créé. Vous pouvez maintenant vous connecter.
				</p>
			<?php endif; ?>
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

			<form action="index.php?uc=auth&amp;action=login" method="post">
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
					<label for="mot_de_passe">Mot de passe</label>
					<input
						type="password"
						id="mot_de_passe"
						name="mot_de_passe"
						autocomplete="current-password"
						required
					>
				</div>

				<button type="submit">Se connecter</button>
			</form>

			<p class="lien-inscription">
				Vous n’avez pas encore de compte ?
				<a href="index.php?uc=auth&amp;action=register">Créer un compte</a>
			</p>
		</section>
	</main>
</body>
</html>
