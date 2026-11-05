<?php
/** @var list<array{id: int|string, libelle: string, prix: string, description: ?string, image: string, type_bois: ?string}> $produits */
/** @var string $erreurCatalogue */
/** @var string $jetonCsrfPanier */

$role = $_SESSION['role'] ?? '';

$echapper = static function (?string $valeur): string {
    return htmlspecialchars($valeur ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nos produits - LINS</title>
    <link rel="stylesheet" href="includes/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/v_header.php'; ?>
    <main class="catalogue">
        <header class="catalogue-entete">
            <p class="catalogue-sur-titre">La sélection LINS</p>

            <h1>Nos produits</h1>

            <p>Découvrez notre gamme de panneaux et de matériaux bois.</p>
        </header>

        <?php if ($erreurCatalogue !== ''): ?>
            <p class="message message-erreur" role="alert"><?= $echapper($erreurCatalogue) ?></p>

        <?php elseif (!$produits): ?>
            <p class="catalogue-vide">Aucun produit n’est disponible pour le moment.</p>

        <?php else: ?>
            <p class="catalogue-compte">
                <?= count($produits) ?> produit<?= count($produits) > 1 ? 's' : '' ?>
            </p>

            <section class="grille-produits" aria-label="Tous les produits">
                <?php foreach ($produits as $produit): ?>
                    <article class="carte-produit">
                        <img
                            class="produit-image"
                            src="includes/images/produits/<?= $echapper(basename($produit['image'])) ?>"
                            alt="<?= $echapper($produit['libelle']) ?>"
                            loading="lazy"
                        >

                        <?php if ($produit['type_bois'] !== null): ?>
                            <p class="produit-categorie"><?= $echapper($produit['type_bois']) ?></p>
                        <?php endif; ?>

                        <h2><?= $echapper($produit['libelle']) ?></h2>

                        <?php if ($produit['description'] !== null && $produit['description'] !== ''): ?>
                            <p class="produit-description"><?= $echapper($produit['description']) ?></p>
                        <?php endif; ?>

                        <p class="produit-prix">
                            <?= number_format((float) $produit['prix'], 2, ',', ' ') ?>&nbsp;€
                        </p>

                        <?php if ($role === 'client'): ?>
                            <form method="post" action="index.php?uc=panier" class="formulaire-ajout-panier">
                                <input type="hidden" name="action" value="ajouter">
                                <input type="hidden" name="id_produit" value="<?= (int) $produit['id'] ?>">

                                <input
                                    type="hidden"
                                    name="jeton_csrf"
                                    value="<?= $echapper($jetonCsrfPanier) ?>"
                                >

                                <button type="submit" class="bouton-inscription">Ajouter au panier</button>
                            </form>

                        <?php elseif ($role === ''): ?>
                            <p class="produit-connexion">
                                <a href="index.php?uc=auth">Connectez-vous pour commander</a>
                            </p>
                        <?php endif; ?>
                    </article>

                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
