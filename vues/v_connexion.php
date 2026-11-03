<?php /** @var bool $inscriptionReussie */ ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - LINS</title>
    <link rel="stylesheet" href="includes/css/style.css">
</head>
<body>
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

            <form method="post" action="index.php?uc=auth&amp;action=login" class="formulaire-inscription">
                <div class="champ">
                    <label for="identifiant">Identifiant</label>
                    <input
                        id="identifiant"
                        name="identifiant"
                        type="text"
                        maxlength="100"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="champ">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input
                        id="mot_de_passe"
                        name="mot_de_passe"
                        type="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="champ">
                    <label for="confirmation">Confirmation du mot de passe</label>
                    <input
                        id="confirmation"
                        name="confirmation"
                        type="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="bouton-inscription">Se connecter</button>
            </form>

            <p class="lien-inscription">
                Vous n’avez pas encore de compte ?
                <a href="index.php?uc=auth&action=register">Créer un compte</a>
            </p>
        </section>
    </main>
</body>
</html>
