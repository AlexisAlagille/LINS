<?php
function verifierConnexion($email, $motdepasse)
{
	global $bdd;

	if (!isset($bdd) || !($bdd instanceof PDO)) {
		return false;
	}

	$requete = $bdd->prepare(
		"SELECT password FROM user WHERE email = :email LIMIT 1"
	);
	$requete->execute(["email" => $email]);
	$utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

	return is_array($utilisateur)
		&& password_verify($motdepasse, $utilisateur["password"]);
}
