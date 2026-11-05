<?php
$erreurConnexion = $erreurConnexion ?? '';
$messageConnexion = $messageConnexion ?? '';
$inscriptionReussie = $inscriptionReussie ?? false;
$echapper = static function (string $valeur): string {
	return htmlspecialchars($valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
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
	<main class="inscription">
		<section class="carte-inscription" aria-labelledby="titre-connexion">
			<header class="entete-inscription">
				<p class="sur-titre">Ravi de vous revoir</p>
				<h1 id="titre-connexion">Connexion</h1>
				<p>Connectez-vous à votre compte LINS.</p>
			</header>

			<?php if ($inscriptionReussie): ?>
				<div class="message message-succes" role="status">
					Votre compte a été créé. Vous pouvez maintenant vous connecter.
				</div>
			<?php endif; ?>
			<?php if ($erreurConnexion !== ''): ?>
				<div class="message message-erreur" role="alert">
					<?= $echapper($erreurConnexion) ?>
				</div>
			<?php endif; ?>
			<?php if ($messageConnexion !== ''): ?>
				<div class="message message-succes" role="status">
					<?= $echapper($messageConnexion) ?>
				</div>
			<?php endif; ?>

			<form action="index.php?uc=auth&amp;action=login" method="post" class="formulaire-inscription">
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

				<button type="submit" class="bouton-inscription">Se connecter</button>
			</form>

			<p class="lien-inscription">
				Vous n’avez pas encore de compte ?
				<a href="index.php?uc=auth&amp;action=register">Créer un compte</a>
			</p>
		</section>
	</main>
	<?php require __DIR__ . '/v_footer.php'; ?>
</body>
</html>
