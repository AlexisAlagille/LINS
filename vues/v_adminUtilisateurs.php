<?php
/**
 * @var list<string> $erreurs
 * @var string $messageSucces
 * @var array{nom: string, prenom: string, email: string} $donnees
 * @var string $roleCreation
 * @var list<array{id: int|string, libelle: string}> $roles
 * @var list<array{id: int|string, nom: string, email: string, role: string, date_creation: string}> $utilisateurs
 * @var int $idCompteAdministrateur
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
    <title>Administration des comptes | LINS</title>
    <link rel="stylesheet" href="includes/css/style.css">
</head>
<body class="page-connexion">
    <?php require __DIR__ . '/v_header.php'; ?>
    <main class="administration-comptes">
        <header class="administration-entete">
            <p class="sur-titre">Espace administrateur</p>
            <h1>Gestion des comptes</h1>
            <p>Consultez les comptes, créez des comptes privilégiés et gérez les rôles.</p>
        </header>

        <?php if ($erreurs): ?>
            <div class="message message-erreur" role="alert">
                <p>L’opération n’a pas pu aboutir :</p>
                <ul>
                    <?php foreach ($erreurs as $erreur): ?>
                        <li><?= $echapper($erreur) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($messageSucces !== ''): ?>
            <div class="message message-succes" role="status"><?= $echapper((string) $messageSucces) ?></div>
        <?php endif; ?>

        <section class="administration-section" aria-labelledby="titre-creer-compte">
            <h2 id="titre-creer-compte">Créer un compte privilégié</h2>
            <form method="post" action="index.php?uc=adminUtilisateurs" class="formulaire-inscription">
                <input type="hidden" name="jeton_csrf" value="<?= $echapper($_SESSION['jeton_csrf_admin_utilisateurs']) ?>">
                <input type="hidden" name="action" value="creer">
                <div class="grille-champs">
                    <div class="champ">
                        <label for="prenom">Prénom</label>
                        <input id="prenom" name="prenom" type="text" maxlength="100" autocomplete="given-name" value="<?= $echapper($donnees['prenom']) ?>" required>
                    </div>
                    <div class="champ">
                        <label for="nom">Nom</label>
                        <input id="nom" name="nom" type="text" maxlength="100" autocomplete="family-name" value="<?= $echapper($donnees['nom']) ?>" required>
                    </div>
                </div>
                <div class="champ">
                    <label for="email">Adresse email</label>
                    <input id="email" name="email" type="email" maxlength="255" autocomplete="email" value="<?= $echapper($donnees['email']) ?>" required>
                </div>
                <div class="champ">
                    <label for="role_id">Rôle</label>
                    <select id="role_id" name="role_id" required>
                        <option value="">Choisir un rôle</option>
                        <?php foreach ($roles as $role): ?>
                            <?php if (in_array($role['libelle'], ['admin', 'gestionnaire'], true)): ?>
                                <option value="<?= (int) $role['id'] ?>" <?= $roleCreation === (string) $role['id'] ? 'selected' : '' ?>>
                                    <?= $echapper(ucfirst((string) $role['libelle'])) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grille-champs">
                    <div class="champ">
                        <label for="mot_de_passe">Mot de passe temporaire</label>
                        <input id="mot_de_passe" name="mot_de_passe" type="password" minlength="12" autocomplete="new-password" required>
                        <small>12 caractères minimum.</small>
                    </div>
                    <div class="champ">
                        <label for="confirmation">Confirmer le mot de passe</label>
                        <input id="confirmation" name="confirmation" type="password" minlength="12" autocomplete="new-password" required>
                    </div>
                </div>
                <button type="submit" class="bouton-inscription">Créer le compte</button>
            </form>
        </section>

        <section class="administration-section" aria-labelledby="titre-liste-comptes">
            <h2 id="titre-liste-comptes">Comptes existants</h2>
            <?php if (!$utilisateurs): ?>
                <p class="accueil-etat">Aucun compte à afficher.</p>
            <?php else: ?>
                <div class="tableau-comptes-conteneur">
                    <table class="tableau-comptes">
                        <thead>
                            <tr>
                                <th scope="col">Nom</th>
                                <th scope="col">Email</th>
                                <th scope="col">Rôle</th>
                                <th scope="col">Date de création</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilisateurs as $utilisateur): ?>
                                <?php $estCompteAdministrateur = (int) $utilisateur['id'] === $idCompteAdministrateur; ?>
                                <tr>
                                    <td><?= $echapper((string) $utilisateur['nom']) ?><?= $estCompteAdministrateur ? ' (vous)' : '' ?></td>
                                    <td><?= $echapper((string) $utilisateur['email']) ?></td>
                                    <td>
                                        <span class="badge-role badge-role-<?= $echapper((string) $utilisateur['role']) ?>">
                                            <?= $echapper(ucfirst((string) $utilisateur['role'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $dateCreation = strtotime((string) $utilisateur['date_creation']);
                                        echo $dateCreation === false
                                            ? 'Date inconnue'
                                            : $echapper(date('d/m/Y H:i', $dateCreation));
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($estCompteAdministrateur): ?>
                                            <span>Votre compte ne peut pas être modifié ici.</span>
                                        <?php else: ?>
                                            <div class="actions-compte">
                                                <form method="post" action="index.php?uc=adminUtilisateurs">
                                                    <input type="hidden" name="jeton_csrf" value="<?= $echapper($_SESSION['jeton_csrf_admin_utilisateurs']) ?>">
                                                    <input type="hidden" name="action" value="modifier_role">
                                                    <input type="hidden" name="utilisateur_id" value="<?= (int) $utilisateur['id'] ?>">
                                                    <label class="sr-seulement" for="role-<?= (int) $utilisateur['id'] ?>">Nouveau rôle pour <?= $echapper((string) $utilisateur['nom']) ?></label>
                                                    <select id="role-<?= (int) $utilisateur['id'] ?>" name="role_id" required>
                                                        <?php foreach ($roles as $role): ?>
                                                            <option value="<?= (int) $role['id'] ?>" <?= $role['libelle'] === $utilisateur['role'] ? 'selected' : '' ?>>
                                                                <?= $echapper(ucfirst((string) $role['libelle'])) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit">Modifier le rôle</button>
                                                </form>
                                                <form method="post" action="index.php?uc=adminUtilisateurs" onsubmit="return confirm('Confirmer la suppression de ce compte ?');">
                                                    <input type="hidden" name="jeton_csrf" value="<?= $echapper($_SESSION['jeton_csrf_admin_utilisateurs']) ?>">
                                                    <input type="hidden" name="action" value="supprimer">
                                                    <input type="hidden" name="utilisateur_id" value="<?= (int) $utilisateur['id'] ?>">
                                                    <button type="submit" class="bouton-suppression">Supprimer</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php require __DIR__ . '/v_footer.php'; ?>
</body>
</html>
