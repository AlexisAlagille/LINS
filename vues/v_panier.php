<?php
/** @var array<int, array{id: int|string, libelle: string, prix: string, description: ?string}> $produitsPanier */
/** @var string $erreurPanier */
/** @var string $messagePanier */
$echapper = static function (?string $valeur): string {
    return htmlspecialchars($valeur ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$totalPanier = 0.0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mon panier - LINS</title>
    <link rel="stylesheet" href="includes/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/v_header.php'; ?>
    <main class="page-panier">
        <header class="catalogue-entete">
            <p class="catalogue-sur-titre">Votre sélection</p>
            <h1>Mon panier</h1>
            <p>Vérifiez les produits et les quantités avant de valider votre commande.</p>
        </header>

        <?php if ($messagePanier !== ''): ?>
            <p class="message message-succes" role="status"><?= $echapper($messagePanier) ?></p>
        <?php endif; ?>
        <?php if ($erreurPanier !== ''): ?>
            <p class="message message-erreur" role="alert"><?= $echapper($erreurPanier) ?></p>
        <?php endif; ?>

        <?php if (!$produitsPanier): ?>
            <p class="catalogue-vide">Votre panier est vide. <a href="index.php?uc=produits">Voir les produits</a></p>
        <?php else: ?>
            <form method="post" action="index.php?uc=panier" class="tableau-panier">
                <input
                    type="hidden"
                    name="jeton_csrf"
                    value="<?= $echapper($_SESSION['jeton_csrf_panier']) ?>"
                >
                <div class="tableau-panier-entete" aria-hidden="true">
                    <span>Produit</span>
                    <span>Prix unitaire</span>
                    <span>Quantité</span>
                    <span>Sous-total</span>
                </div>
                <?php foreach ($produitsPanier as $idProduit => $produit): ?>
                    <?php
                    $quantite = (int) ($_SESSION['panier'][$idProduit] ?? 0);
                    $prixEnCentimes = (int) round((float) $produit['prix'] * 100);
                    $sousTotalEnCentimes = $prixEnCentimes * $quantite;
                    $totalPanier += $sousTotalEnCentimes;
                    ?>
                    <div class="ligne-panier">
                        <strong><?= $echapper($produit['libelle']) ?></strong>
                        <span><?= number_format((float) $produit['prix'], 2, ',', ' ') ?>&nbsp;€</span>
                        <label>
                            <span class="texte-visuellement-cache">Quantité de <?= $echapper($produit['libelle']) ?></span>
                            <input
                                type="number"
                                name="quantites[<?= (int) $idProduit ?>]"
                                min="1"
                                value="<?= $quantite ?>"
                                data-prix-centimes="<?= $prixEnCentimes ?>"
                                required
                            >
                        </label>
                        <strong class="sous-total">
                            <?= number_format($sousTotalEnCentimes / 100, 2, ',', ' ') ?>&nbsp;€
                        </strong>
                        <button
                            type="submit"
                            name="retirer"
                            value="<?= (int) $idProduit ?>"
                            class="bouton-lien"
                        >Retirer</button>
                    </div>
                <?php endforeach; ?>
                <div class="resume-panier">
                    <a href="index.php?uc=produits" class="bouton-secondaire">
                        Continuer mes achats
                    </a>

                    <button type="submit" name="action" value="commander" class="bouton-inscription">
                        Valider la commande
                    </button>

                    <p>
                        Total estimé :
                        <strong id="total-panier" aria-live="polite">
                            <?= number_format($totalPanier / 100, 2, ',', ' ') ?>&nbsp;€
                        </strong>
                    </p>
                </div>
            </form>
        <?php endif; ?>
    </main>
    <script>
        const formatPrix = new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        function mettreAJourPrix() {
            let total = 0;

            document.querySelectorAll('input[name^="quantites["]').forEach((champ) => {
                const quantite = Number(champ.value) || 0;
                const sousTotal = Number(champ.dataset.prixCentimes) * quantite;
                total += sousTotal;

                champ.closest('.ligne-panier').querySelector('.sous-total').textContent =
                    formatPrix.format(sousTotal / 100) + ' €';
            });

            document.querySelector('#total-panier').textContent =
                formatPrix.format(total / 100) + ' €';
        }

        document.querySelectorAll('input[name^="quantites["]').forEach((champ) => {
            champ.addEventListener('input', mettreAJourPrix);
        });
    </script>
</body>
</html>
