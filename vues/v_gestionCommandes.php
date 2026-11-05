<?php
/**
 * @var list<string> $erreurs
 * @var string $messageSucces
 * @var list<array{id: int|string, libelle: string}> $statuts
 * @var string $statutFiltre
 * @var list<array{id: int|string, id_statut: int|string, statut: string, date_commande: string, nom_client: string, prenom_client: string, email: string, total: int|float|string, lignes: list<array{libelle_produit: string, quantite: int|string, prix_unitaire: int|float|string}>}> $commandes
 */
$echapper = static function (string $valeur): string {
    return htmlspecialchars($valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestion des commandes | LINS</title>
    <link rel="stylesheet" href="includes/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/v_header.php'; ?>
    <main class="gestion-commandes">
        <header class="gestion-commandes-entete">
            <p class="catalogue-sur-titre">Espace gestionnaire</p>
            <h1>Gestion des commandes</h1>
        </header>

        <?php if ($messageSucces !== ''): ?>
            <p class="message message-succes" role="status"><?= $echapper($messageSucces) ?></p>
        <?php endif; ?>

        <?php if ($erreurs): ?>
            <div class="message message-erreur" role="alert">
                <ul>
                    <?php foreach ($erreurs as $erreur): ?>
                        <li><?= $echapper($erreur) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="get" action="index.php" class="filtre-commandes">
            <input type="hidden" name="uc" value="gestionCommandes">
            <label for="statut_id">Filtrer par statut</label>
            <select id="statut_id" name="statut_id">
                <option value="">Tous les statuts</option>
                <?php foreach ($statuts as $statut): ?>
                    <option value="<?= (int) $statut['id'] ?>" <?= $statutFiltre === (string) $statut['id'] ? 'selected' : '' ?>>
                        <?= $echapper((string) $statut['libelle']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bouton-inscription">Filtrer</button>
        </form>

        <?php if (!$commandes): ?>
            <p class="catalogue-vide">Aucune commande ne correspond à ce filtre.</p>
        <?php else: ?>
            <div class="tableau-gestion-commandes-conteneur">
                <table class="tableau-gestion-commandes">
                    <thead>
                        <tr>
                            <th scope="col">Commande</th>
                            <th scope="col">Client</th>
                            <th scope="col">Date</th>
                            <th scope="col">Montant</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Détail et mise à jour</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $commande): ?>
                            <?php
                            $timestamp = strtotime((string) $commande['date_commande']);
                            $dateAffichee = $timestamp === false
                                ? (string) $commande['date_commande']
                                : date('d/m/Y H:i', $timestamp);
                            ?>
                            <tr>
                                <td>#<?= (int) $commande['id'] ?></td>
                                <td>
                                    <?= $echapper(trim((string) $commande['prenom_client'] . ' ' . (string) $commande['nom_client'])) ?>
                                    <small><?= $echapper((string) $commande['email']) ?></small>
                                </td>
                                <td><?= $echapper($dateAffichee) ?></td>
                                <td><?= number_format((float) $commande['total'], 2, ',', ' ') ?>&nbsp;€</td>
                                <td><?= $echapper((string) $commande['statut']) ?></td>
                                <td>
                                    <details class="detail-gestion-commande">
                                        <summary>Consulter</summary>
                                        <ul>
                                            <?php foreach ($commande['lignes'] as $ligne): ?>
                                                <li>
                                                    <?= $echapper((string) $ligne['libelle_produit']) ?>
                                                    × <?= (int) $ligne['quantite'] ?>
                                                    — <?= number_format((float) $ligne['prix_unitaire'], 2, ',', ' ') ?>&nbsp;€ / unité
                                                    (<?= number_format((float) $ligne['prix_unitaire'] * (int) $ligne['quantite'], 2, ',', ' ') ?>&nbsp;€)
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <form method="post" action="index.php?uc=gestionCommandes" class="formulaire-statut-commande">
                                            <input type="hidden" name="jeton_csrf" value="<?= $echapper($_SESSION['jeton_csrf_gestion_commandes']) ?>">
                                            <input type="hidden" name="action" value="modifier_statut">
                                            <input type="hidden" name="id_commande" value="<?= (int) $commande['id'] ?>">
                                            <label for="statut-commande-<?= (int) $commande['id'] ?>">Nouveau statut</label>
                                            <select id="statut-commande-<?= (int) $commande['id'] ?>" name="id_statut" required>
                                                <?php foreach ($statuts as $statut): ?>
                                                    <option value="<?= (int) $statut['id'] ?>" <?= (int) $statut['id'] === (int) $commande['id_statut'] ? 'selected' : '' ?>>
                                                        <?= (int) $statut['id'] ?> - <?= $echapper((string) $statut['libelle']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="bouton-inscription">Mettre à jour</button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/v_footer.php'; ?>
</body>
</html>
