<?php
function creerUtilisateur(PDO $bdd, array $donnees, string $motDePasse): void
{
    $nomAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $donnees['nom']);
    $prenomAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $donnees['prenom']);

    if ($nomAscii === false || $prenomAscii === false) {
        throw new RuntimeException('Impossible de générer un identifiant à partir du nom et du prénom.');
    }

    $lettresNom = preg_replace('/[^A-Za-z]/', '', $nomAscii) ?? '';
    $lettresPrenom = preg_replace('/[^A-Za-z]/', '', $prenomAscii) ?? '';

    if ($lettresNom === '' || $lettresPrenom === '') {
        throw new InvalidArgumentException('Le nom et le prénom doivent contenir des lettres pour générer l’identifiant.');
    }

    $identifiantBase = strtoupper(substr($lettresNom, 0, 1) . substr($lettresPrenom, 0, 6));

    $role = $bdd->prepare('SELECT id FROM role WHERE libelle = ?');
    $role->execute(['client']);
    $idRole = $role->fetchColumn();

    if ($idRole === false) {
        throw new RuntimeException('Le rôle client est absent de la base de données.');
    }

    $rechercheEmail = $bdd->prepare('SELECT id FROM user WHERE email = ? LIMIT 1');
    $rechercheEmail->execute([$donnees['email']]);

    if ($rechercheEmail->fetchColumn() !== false) {
        throw new InvalidArgumentException('Cette adresse email est déjà utilisée.');
    }

    $rechercheIdentifiant = $bdd->prepare('SELECT id FROM user WHERE identifiant = ? LIMIT 1');
    $identifiant = $identifiantBase;
    $suffixe = 1;

    do {
        $rechercheIdentifiant->execute([$identifiant]);
        $identifiantExistant = $rechercheIdentifiant->fetchColumn() !== false;
        if ($identifiantExistant) {
            $identifiant = $identifiantBase . ++$suffixe;
        }
    } while ($identifiantExistant);

    $insertion = $bdd->prepare(
        'INSERT INTO user (identifiant, password, nom, prenom, email, id_role)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insertion->execute([
        $identifiant,
        password_hash($motDePasse, PASSWORD_DEFAULT),
        $donnees['nom'],
        $donnees['prenom'],
        $donnees['email'],
        $idRole,
    ]);
}

function getUserRole(PDO $bdd, string $email): ?string
{
    $requete = $bdd->prepare(
        'SELECT role.libelle
        FROM user
        JOIN role ON role.id = user.id_role
        WHERE user.email = ?
        LIMIT 1'
    );
    $requete->execute([$email]);
    $role = $requete->fetchColumn();

    return $role === false ? null : $role;
}

function verifierConnexion(PDO $bdd, string $email, string $motdepasse): bool
{
	$requete = $bdd->prepare(
		'SELECT password FROM user WHERE email = ? LIMIT 1'
	);
	$requete->execute([$email]);
	$utilisateur = $requete->fetch();

	return is_array($utilisateur)
		&& password_verify($motdepasse, $utilisateur["password"]);
}

function listerProduitsAccueil(PDO $bdd, int $limite = 6): array
{
    $limite = max(1, min($limite, 12));
    $requete = $bdd->prepare(
        'SELECT id, libelle, prix, description FROM produit ORDER BY id DESC LIMIT ?'
    );
    $requete->execute([$limite]);

    return $requete->fetchAll();
}

function getTousLesProduits(
    PDO $bdd,
    int $limite,
    int $decalage,
    string $recherche,
    int $idTypeBois
): array
{
    $requete = $bdd->prepare(
        'SELECT produit.id, produit.libelle, produit.prix, produit.description, produit.image,
                type_bois.libelle AS type_bois
         FROM produit
         LEFT JOIN type_bois ON type_bois.id = produit.id_type_bois
         WHERE (? = \'\' OR produit.libelle LIKE ? OR produit.description LIKE ?)
         AND (? = 0 OR produit.id_type_bois = ?)
         ORDER BY type_bois.libelle, produit.libelle
         LIMIT ? OFFSET ?'
    );
    $motRecherche = '%' . $recherche . '%';
    $requete->execute([
        $recherche,
        $motRecherche,
        $motRecherche,
        $idTypeBois,
        $idTypeBois,
        $limite,
        $decalage,
    ]);

    return $requete->fetchAll();
}

function compterProduits(PDO $bdd, string $recherche, int $idTypeBois): int
{
    $requete = $bdd->prepare(
        'SELECT COUNT(*)
         FROM produit
         WHERE (? = \'\' OR libelle LIKE ? OR description LIKE ?)
         AND (? = 0 OR id_type_bois = ?)'
    );
    $motRecherche = '%' . $recherche . '%';
    $requete->execute([
        $recherche,
        $motRecherche,
        $motRecherche,
        $idTypeBois,
        $idTypeBois,
    ]);

    return (int) $requete->fetchColumn();
}

function getTypesBois(PDO $bdd): array
{
    $requete = $bdd->query('SELECT id, libelle FROM type_bois ORDER BY libelle');

    return $requete->fetchAll();
}

function getProduitsParIds(PDO $bdd, array $ids): array
{
    $requete = $bdd->prepare('SELECT id, libelle, prix, description FROM produit WHERE id = ?');
    $produits = [];

    foreach ($ids as $id) {
        $requete->execute([$id]);
        $produit = $requete->fetch();

        if ($produit) {
            $produits[(int) $produit['id']] = $produit;
        }
    }

    return $produits;
}

function getIdUtilisateur(PDO $bdd, string $email): int
{
    $requete = $bdd->prepare('SELECT id FROM user WHERE email = ? LIMIT 1');
    $requete->execute([$email]);
    $idUtilisateur = $requete->fetchColumn();

    if ($idUtilisateur === false) {
        throw new RuntimeException('Le compte client est introuvable.');
    }

    return (int) $idUtilisateur;
}

function creerCommande(PDO $bdd, int $idUtilisateur, array $articles): int
{
    if (!$articles) {
        throw new InvalidArgumentException('Le panier est vide.');
    }

    foreach ($articles as $idProduit => $quantite) {
        if (!is_int($idProduit) || $idProduit < 1 || !is_int($quantite) || $quantite < 1) {
            throw new InvalidArgumentException('Le contenu du panier est invalide.');
        }
    }

    $produits = getProduitsParIds($bdd, array_keys($articles));
    if (count($produits) !== count($articles)) {
        throw new InvalidArgumentException('Un produit du panier n’est plus disponible. Actualisez le panier.');
    }

    $requete = $bdd->prepare('SELECT id FROM statut WHERE libelle = ?');
    $requete->execute(['en attente']);
    $idStatut = $requete->fetchColumn();

    if ($idStatut === false) {
        throw new RuntimeException('Le statut « en attente » est absent de la base.');
    }

    // Enregistrer la commande et ses lignes ensemble.
    $bdd->beginTransaction();
    try {
        $insertionCommande = $bdd->prepare(
            'INSERT INTO commande (id_client, id_statut) VALUES (?, ?)'
        );
        $insertionCommande->execute([$idUtilisateur, $idStatut]);
        $idCommande = (int) $bdd->lastInsertId();

        $insertionLigne = $bdd->prepare(
            'INSERT INTO ligne_commande (id_commande, id_produit, qteProduit)
             VALUES (?, ?, ?)'
        );
        foreach ($articles as $idProduit => $quantite) {
            $insertionLigne->execute([$idCommande, $idProduit, $quantite]);
        }

        $bdd->commit();
        return $idCommande;
    } catch (Throwable $e) {
        if ($bdd->inTransaction()) {
            $bdd->rollBack();
        }
        throw $e;
    }
}

function getCommandes(PDO $bdd, ?int $idUtilisateur = null): array
{
    if ($idUtilisateur !== null) {
        $requete = $bdd->prepare(
            'SELECT commande.id, statut.libelle AS statut, commande.date_commande, user.email,
                    COALESCE(SUM(produit.prix * ligne_commande.qteProduit), 0) AS total
             FROM commande
             JOIN user ON user.id = commande.id_client
             JOIN statut ON statut.id = commande.id_statut
             LEFT JOIN ligne_commande ON ligne_commande.id_commande = commande.id
             LEFT JOIN produit ON produit.id = ligne_commande.id_produit
             WHERE commande.id_client = ?
             GROUP BY commande.id, statut.libelle, commande.date_commande, user.email
             ORDER BY commande.date_commande DESC, commande.id DESC'
        );
        $requete->execute([$idUtilisateur]);
    } else {
        $requete = $bdd->query(
            'SELECT commande.id, statut.libelle AS statut, commande.date_commande, user.email,
                    COALESCE(SUM(produit.prix * ligne_commande.qteProduit), 0) AS total
             FROM commande
             JOIN user ON user.id = commande.id_client
             JOIN statut ON statut.id = commande.id_statut
             LEFT JOIN ligne_commande ON ligne_commande.id_commande = commande.id
             LEFT JOIN produit ON produit.id = ligne_commande.id_produit
             GROUP BY commande.id, statut.libelle, commande.date_commande, user.email
             ORDER BY commande.date_commande DESC, commande.id DESC'
        );
    }

    return $requete->fetchAll();
}

function annulerCommande(PDO $bdd, int $idCommande, int $idUtilisateur): bool
{
    $requeteStatuts = $bdd->prepare(
        'SELECT id, libelle FROM statut WHERE libelle IN (?, ?)'
    );
    $requeteStatuts->execute(['en attente', 'validée']);

    $idsStatutsAnnulables = [];
    foreach ($requeteStatuts->fetchAll() as $statut) {
        $idsStatutsAnnulables[$statut['libelle']] = (int) $statut['id'];
    }

    if (!isset($idsStatutsAnnulables['en attente'], $idsStatutsAnnulables['validée'])) {
        throw new RuntimeException('Les statuts nécessaires à l’annulation sont absents de la base.');
    }

    $bdd->beginTransaction();
    try {
        $verifierCommande = $bdd->prepare(
            'SELECT id FROM commande
             WHERE id = ? AND id_client = ? AND id_statut IN (?, ?)'
             . ($bdd->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '')
        );
        $verifierCommande->execute([
            $idCommande,
            $idUtilisateur,
            $idsStatutsAnnulables['en attente'],
            $idsStatutsAnnulables['validée'],
        ]);

        if ($verifierCommande->fetchColumn() === false) {
            $bdd->rollBack();
            return false;
        }

        $supprimerLignes = $bdd->prepare('DELETE FROM ligne_commande WHERE id_commande = ?');
        $supprimerLignes->execute([$idCommande]);

        $supprimerCommande = $bdd->prepare(
            'DELETE FROM commande
             WHERE id = ? AND id_client = ? AND id_statut IN (?, ?)'
        );
        $supprimerCommande->execute([
            $idCommande,
            $idUtilisateur,
            $idsStatutsAnnulables['en attente'],
            $idsStatutsAnnulables['validée'],
        ]);

        if ($supprimerCommande->rowCount() !== 1) {
            throw new RuntimeException('La commande n’a pas pu être supprimée.');
        }

        $bdd->commit();
        return true;
    } catch (Throwable $e) {
        if ($bdd->inTransaction()) {
            $bdd->rollBack();
        }
        throw $e;
    }
}

function getLignesCommandes(PDO $bdd, array $idsCommandes): array
{
    $lignesParCommande = [];
    $requete = $bdd->prepare(
        'SELECT ligne_commande.id_commande, produit.libelle AS libelle_produit,
                produit.prix AS prix_unitaire, ligne_commande.qteProduit AS quantite
         FROM ligne_commande
         JOIN produit ON produit.id = ligne_commande.id_produit
         WHERE id_commande = ?
         ORDER BY ligne_commande.id_produit'
    );
    foreach ($idsCommandes as $idCommande) {
        $requete->execute([$idCommande]);
        $lignesParCommande[$idCommande] = $requete->fetchAll();
    }

    return $lignesParCommande;
}

function obtenirInformationsUtilisateur(PDO $bdd, string $email): ?array
{
    $requete = $bdd->prepare(
        'SELECT identifiant, nom, prenom, email FROM user WHERE email = ? LIMIT 1'
    );
    $requete->execute([$email]);
    $utilisateur = $requete->fetch();

    return is_array($utilisateur) ? $utilisateur : null;
}

function modifierInformationsUtilisateur(PDO $bdd, string $emailActuel, array $donnees): void
{
    $rechercheEmail = $bdd->prepare(
        'SELECT id FROM user WHERE email = ? AND email <> ? LIMIT 1'
    );
    $rechercheEmail->execute([$donnees['email'], $emailActuel]);

    if ($rechercheEmail->fetchColumn() !== false) {
        throw new InvalidArgumentException('Cette adresse email est déjà utilisée.');
    }

    $miseAJour = $bdd->prepare(
        'UPDATE user SET nom = ?, prenom = ?, email = ? WHERE email = ?'
    );
    $miseAJour->execute([
        $donnees['nom'],
        $donnees['prenom'],
        $donnees['email'],
        $emailActuel,
    ]);
}

function listerRoles(PDO $bdd): array
{
    $requete = $bdd->query('SELECT id, libelle FROM role ORDER BY libelle');

    return $requete->fetchAll();
}

function listerUtilisateursAdmin(PDO $bdd): array
{
    $requete = $bdd->query(
        'SELECT user.id, user.nom, user.email, role.libelle AS role, user.date_creation
         FROM user
         JOIN role ON role.id = user.id_role
         ORDER BY user.nom, user.email'
    );

    return $requete->fetchAll();
}

function creerUtilisateurAdmin(PDO $bdd, array $donnees, string $motDePasse, int $idRole): void
{
    $nomAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $donnees['nom']);
    $prenomAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $donnees['prenom']);

    if ($nomAscii === false || $prenomAscii === false) {
        throw new RuntimeException('Impossible de générer un identifiant à partir du nom et du prénom.');
    }

    $lettresNom = preg_replace('/[^A-Za-z]/', '', $nomAscii) ?? '';
    $lettresPrenom = preg_replace('/[^A-Za-z]/', '', $prenomAscii) ?? '';
    if ($lettresNom === '' || $lettresPrenom === '') {
        throw new InvalidArgumentException('Le nom et le prénom doivent contenir des lettres.');
    }

    $rechercheEmail = $bdd->prepare('SELECT id FROM user WHERE email = ? LIMIT 1');
    $rechercheEmail->execute([$donnees['email']]);
    if ($rechercheEmail->fetchColumn() !== false) {
        throw new InvalidArgumentException('Cette adresse email est déjà utilisée.');
    }

    $rechercheRole = $bdd->prepare('SELECT libelle FROM role WHERE id = ? LIMIT 1');
    $rechercheRole->execute([$idRole]);
    $libelleRole = $rechercheRole->fetchColumn();
    if (!in_array($libelleRole, ['admin', 'gestionnaire'], true)) {
        throw new InvalidArgumentException('Seuls les comptes administrateur ou gestionnaire peuvent être créés ici.');
    }

    $identifiantBase = strtoupper(substr($lettresNom, 0, 1) . substr($lettresPrenom, 0, 6));
    $rechercheIdentifiant = $bdd->prepare('SELECT id FROM user WHERE identifiant = ? LIMIT 1');
    $identifiant = $identifiantBase;
    $suffixe = 1;
    do {
        $rechercheIdentifiant->execute([$identifiant]);
        $identifiantExistant = $rechercheIdentifiant->fetchColumn() !== false;
        if ($identifiantExistant) {
            $identifiant = $identifiantBase . ++$suffixe;
        }
    } while ($identifiantExistant);

    $insertion = $bdd->prepare(
        'INSERT INTO user (identifiant, password, nom, prenom, email, id_role)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insertion->execute([
        $identifiant,
        password_hash($motDePasse, PASSWORD_DEFAULT),
        $donnees['nom'],
        $donnees['prenom'],
        $donnees['email'],
        $idRole,
    ]);
}

function modifierRoleUtilisateur(PDO $bdd, int $idUtilisateur, int $idRole): void
{
    $requete = $bdd->prepare('UPDATE user SET id_role = ? WHERE id = ?');
    $requete->execute([$idRole, $idUtilisateur]);
}

function supprimerUtilisateur(PDO $bdd, int $idUtilisateur): void
{
    $requete = $bdd->prepare('DELETE FROM user WHERE id = ?');
    $requete->execute([$idUtilisateur]);
}
