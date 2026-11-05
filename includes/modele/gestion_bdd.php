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
	$utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

	return is_array($utilisateur)
		&& password_verify($motdepasse, $utilisateur["password"]);
}

function listerProduitsAccueil(PDO $bdd, int $limite = 6): array
{
    $limite = max(1, min($limite, 12));
    $requete = $bdd->prepare(
        'SELECT id, libelle, prix, description FROM produit ORDER BY id DESC LIMIT :limite'
    );
    $requete->bindValue(':limite', $limite, PDO::PARAM_INT);
    $requete->execute();

    return $requete->fetchAll();
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
