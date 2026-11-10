<?php
/** @var list<array{id: int|string, statut: string, total: string, date_commande: string, email: string, lignes: array}> $commandes */
/** @var string $erreurCommandes */
/** @var string $messageCommande */
$echapper = static function (?string $valeur): string {
    return htmlspecialchars($valeur ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= ($_SESSION['role'] ?? '') === 'admin' ? 'Commandes' : 'Mes commandes' ?> - LINS</title>
    <link rel="stylesheet" href="includes/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/v_header.php'; ?>
    <main class="page-panier">
        <header class="catalogue-entete">
            <p class="catalogue-sur-titre">Suivi des achats</p>
            <h1><?= ($_SESSION['role'] ?? '') === 'admin' ? 'Commandes' : 'Mes commandes' ?></h1>
            <p>Retrouvez les commandes enregistrées sur votre compte.</p>
        </header>
        <?php if ($messageCommande !== ''): ?>
            <p class="message message-succes" role="status"><?= $echapper($messageCommande) ?></p>
        <?php endif; ?>
        <?php if ($erreurCommandes !== ''): ?>
            <p class="message message-erreur" role="alert"><?= $echapper($erreurCommandes) ?></p>
        <?php elseif (!$commandes): ?>
            <p class="catalogue-vide">Aucune commande n’a encore été enregistrée.</p>
        <?php else: ?>
            <section class="liste-commandes" aria-label="Liste des commandes">
                <?php foreach ($commandes as $commande): ?>
                    <article class="carte-commande">
                        <header>
                            <h2>Commande n°<?= (int) $commande['id'] ?></h2>
                            <p><?= $echapper($commande['date_commande']) ?> · <?= $echapper($commande['statut']) ?></p>
                            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                                <p>Client : <?= $echapper($commande['email']) ?></p>
                            <?php endif; ?>
                        </header>
                        <ul>
                            <?php foreach ($commande['lignes'] as $ligne): ?>
                                <li>
                                    <?= $echapper($ligne['libelle_produit']) ?>
                                    × <?= (int) $ligne['quantite'] ?>
                                    — <?= number_format((float) $ligne['prix_unitaire'], 2, ',', ' ') ?>&nbsp;€ l’unité
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="total-commande">Total : <strong><?= number_format((float) $commande['total'], 2, ',', ' ') ?>&nbsp;€</strong></p>
                        <?php if (($_SESSION['role'] ?? '') === 'client' && in_array($commande['statut'], ['en attente', 'validée'], true)): ?>
                            <form
                                method="post"
                                action="index.php?uc=commandes"
                                onsubmit="return confirm('Voulez-vous vraiment annuler cette commande ?');"
                            >
                                <input type="hidden" name="action" value="annuler">
                                <input type="hidden" name="id_commande" value="<?= (int) $commande['id'] ?>">
                                <input
                                    type="hidden"
                                    name="jeton_csrf"
                                    value="<?= $echapper($_SESSION['jeton_csrf_commandes']) ?>"
                                >
                                <button type="submit" class="bouton-annuler-commande">Annuler la commande</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
