<?php
function creerUtilisateur(PDO $bdd, array $donnees, string $motDePasse): void
{
    $role = $bdd->prepare('SELECT id FROM role WHERE libelle = ?');
    $role->execute(['client']);
    $idRole = $role->fetchColumn();

    if ($idRole === false) {
        throw new RuntimeException('Le rôle client est absent de la base de données.');
    }

    $recherche = $bdd->prepare(
        'SELECT id FROM user WHERE identifiant = ? OR email = ? LIMIT 1'
    );
    $recherche->execute([$donnees['identifiant'], $donnees['email']]);

    if ($recherche->fetchColumn() !== false) {
        throw new InvalidArgumentException('Cet identifiant ou cette adresse email est déjà utilisé.');
    }

    $insertion = $bdd->prepare(
        'INSERT INTO user (identifiant, password, nom, prenom, email, id_role)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insertion->execute([
        $donnees['identifiant'],
        password_hash($motDePasse, PASSWORD_DEFAULT),
        $donnees['nom'],
        $donnees['prenom'],
        $donnees['email'],
        $idRole,
    ]);
}