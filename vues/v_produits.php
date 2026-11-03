<?php
/** @var list<array{libelle: string, prix: string, description: ?string, type_bois: ?string}> $produits */
/** @var string $erreurCatalogue */
$echapper = static function (?string $valeur): string {
    return htmlspecialchars($valeur ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$imageProduit = static function (string $libelle): string {
    $nom = strtolower($libelle);

    if (strpos($nom, 'particules') !== false) {
        return 'includes/images/produits/particules.png';
    }
    if (strpos($nom, 'mdf') !== false) {
        if (strpos($nom, 'hydrofuge') !== false) {
            return 'includes/images/produits/mdf-hydrofuge.png';
        }
        if (strpos($nom, 'laqu') !== false) {
            return 'includes/images/produits/mdf-laque-blanc.png';
        }
        return 'includes/images/produits/mdf.png';
    }
    if (strpos($nom, 'osb') !== false) {
        return 'includes/images/produits/osb.png';
    }

    return 'includes/images/produits/structurel.png';
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
                            src="<?= $echapper($imageProduit($produit['libelle'])) ?>"
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
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
