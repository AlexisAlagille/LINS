<?php
/**
 * @var list<string> $erreurs
 * @var string $messageSucces
 * @var array{nom: string, prenom: string, email: string} $donnees
 */
$jetonCsrf = isset($_SESSION['jeton_csrf_mon_compte']) && is_string($_SESSION['jeton_csrf_mon_compte'])
    ? $_SESSION['jeton_csrf_mon_compte']
    : '';
$echapper = static function (string $valeur): string {
    return htmlspecialchars($valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mon compte | LINS</title>
    <link rel="stylesheet" href="includes/css/style.css">
</head>
<body class="page-connexion">
    <?php require __DIR__ . '/v_header.php'; ?>
    <main class="inscription">
        <section class="carte-inscription" aria-labelledby="titre-mon-compte">
            <header class="entete-inscription">
                <p class="sur-titre">Espace personnel</p>
                <h1 id="titre-mon-compte">Mon compte</h1>
                <p>Consultez et modifiez vos informations personnelles.</p>
            </header>

            <?php if ($erreurs): ?>
                <div class="message message-erreur" role="alert">
                    <p>La mise à jour a échoué :</p>
                    <ul>
                        <?php foreach ($erreurs as $erreur): ?>
                            <li><?= $echapper($erreur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($messageSucces !== ''): ?>
                <div class="message message-succes" role="status">
                    <?= $echapper((string) $messageSucces) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="index.php?uc=monCompte" class="formulaire-inscription">
                <input
                    type="hidden"
                    name="jeton_csrf"
                    value="<?= $echapper($jetonCsrf) ?>"
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

                <button type="submit" class="bouton-inscription">Enregistrer mes informations</button>
            </form>
        </section>
    </main>
    <?php require __DIR__ . '/v_footer.php'; ?>
</body>
</html>
