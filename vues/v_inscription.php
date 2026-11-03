<?php
/** @var array{nom: string, prenom: string, email: string} $donnees */
/** @var list<string> $erreurs */
/** @var bool $inscriptionReussie */
$echapper = static function (string $valeur): string {
    return htmlspecialchars($valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Créer un compte - LINS</title>
    <link rel="stylesheet" href="includes/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/v_header.php'; ?>
    <main class="inscription">
        <section class="carte-inscription" aria-labelledby="titre-inscription">
            <header class="entete-inscription">
                <p class="sur-titre">Bienvenue chez LINS</p>
                <h1 id="titre-inscription">Créer votre compte</h1>
                <p>Renseignez vos informations pour rejoindre notre boutique.</p>
            </header>

            <?php if ($inscriptionReussie): ?>
                <div class="message message-succes" role="status">
                    Votre compte a bien été créé.
                </div>
            <?php endif; ?>

            <?php if ($erreurs): ?>
                <div class="message message-erreur" role="alert">
                    <p>La création du compte a échoué :</p>
                    <ul>
                        <?php foreach ($erreurs as $erreur): ?>
                            <li><?= $echapper($erreur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="index.php?uc=auth&amp;action=register" class="formulaire-inscription">
                <input
                    type="hidden"
                    name="jeton_csrf"
                    value="<?= $echapper($_SESSION['jeton_csrf_inscription']) ?>"
                >

                <div class="grille-champs">
                    <div class="champ">
                        <label for="prenom">Prénom</label>
                        <input
                            id="prenom"
                            name="prenom"
                            type="text"
                            maxlength="100"
                            autocomplete="given-name"
                            value="<?= $echapper($donnees['prenom']) ?>"
                            required
                        >
                    </div>
                    <div class="champ">
                        <label for="nom">Nom</label>
                        <input
                            id="nom"
                            name="nom"
                            type="text"
                            maxlength="100"
                            autocomplete="family-name"
                            value="<?= $echapper($donnees['nom']) ?>"
                            required
                        >
                    </div>
                </div>

                <div class="champ">
                    <label for="email">Adresse email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        maxlength="255"
                        autocomplete="email"
                        value="<?= $echapper($donnees['email']) ?>"
                        required
                    >
                </div>

                <div class="champ">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input
                        id="mot_de_passe"
                        name="mot_de_passe"
                        type="password"
                        minlength="12"
                        autocomplete="new-password"
                        aria-describedby="aide-mot-de-passe"
                        required
                    >
                    <small id="aide-mot-de-passe">12 caractères minimum.</small>
                </div>

                <div class="champ">
                    <label for="confirmation">Confirmer le mot de passe</label>
                    <input
                        id="confirmation"
                        name="confirmation"
                        type="password"
                        minlength="12"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button type="submit" class="bouton-inscription">Créer mon compte</button>
            </form>
        </section>
    </main>
</body>
</html>
