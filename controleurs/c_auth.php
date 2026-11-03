<?php
require_once __DIR__ . "/../includes/modele/gestion_bdd.php";

if (!isset($_REQUEST['action']))
    $action = "connexion";
else
    $action = $_REQUEST['action'];

switch ($action)
{
    case "connexion" : {
        require __DIR__ . "/../vues/v_connexion.php";
        break;
    }

    case "verifier" : {
        $email = trim($_POST["email"] ?? "");
        $motdepasse = $_POST["motdepasse"] ?? "";

        if ($_SERVER["REQUEST_METHOD"] === "POST"
            && $email !== ""
            && $motdepasse !== ""
            && verifierConnexion($email, $motdepasse)) {
            $_SESSION["email"] = $email;
            $messageConnexion = "Connexion réussie.";
        } else {
            $erreurConnexion = "Identifiant ou mot de passe incorrect.";
        }

        require __DIR__ . "/../vues/v_connexion.php";
        break;
    }
}

?>