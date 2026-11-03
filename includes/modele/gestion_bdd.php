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

function getTousLesProduits(PDO $bdd): array
{
    $requete = $bdd->query(
        'SELECT produit.libelle, produit.prix, produit.description, type_bois.libelle AS type_bois
         FROM produit
         LEFT JOIN type_bois ON type_bois.id = produit.id_type_bois
         ORDER BY type_bois.libelle, produit.libelle'
    );

    return $requete->fetchAll(PDO::FETCH_ASSOC);
}
