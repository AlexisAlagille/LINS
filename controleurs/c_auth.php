<?php
$action = isset($_GET['action']) && is_string($_GET['action'])
    ? $_GET['action']
    : 'login';
$inscriptionReussie = isset($_SESSION['message_inscription']);

if ($inscriptionReussie) {
    unset($_SESSION['message_inscription']);
}

switch ($action) {
    case 'register':
$erreurs = [];
$donnees = [
    'identifiant' => '',
    'nom' => '',
    'prenom' => '',
    'email' => '',
];
if (empty($_SESSION['jeton_csrf_inscription'])) {
    $_SESSION['jeton_csrf_inscription'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($donnees as $champ => $valeur) {
        if (isset($_POST[$champ]) && is_string($_POST[$champ])) {
            $donnees[$champ] = trim($_POST[$champ]);
        }
    }

    $motDePasse = isset($_POST['mot_de_passe']) && is_string($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';

    $confirmation = isset($_POST['confirmation']) && is_string($_POST['confirmation']) ? $_POST['confirmation'] : '';

    $jetonCsrf = isset($_POST['jeton_csrf']) && is_string($_POST['jeton_csrf']) ? $_POST['jeton_csrf'] : '';

    if (!hash_equals($_SESSION['jeton_csrf_inscription'], $jetonCsrf)) {
        $erreurs[] = 'Le formulaire a expiré. Veuillez le soumettre à nouveau.';
    }

    if ($donnees['identifiant'] === '' || strlen($donnees['identifiant']) > 100) {
        $erreurs[] = 'L’identifiant est obligatoire et ne doit pas dépasser 100 caractères.';
    }
    if ($donnees['nom'] === '' || strlen($donnees['nom']) > 100) {
        $erreurs[] = 'Le nom est obligatoire et ne doit pas dépasser 100 caractères.';
    }
    if ($donnees['prenom'] === '' || strlen($donnees['prenom']) > 100) {
        $erreurs[] = 'Le prénom est obligatoire et ne doit pas dépasser 100 caractères.';
    }
    if (
        $donnees['email'] === ''
        || strlen($donnees['email']) > 255
        || filter_var($donnees['email'], FILTER_VALIDATE_EMAIL) === false
    ) {
        $erreurs[] = 'Veuillez saisir une adresse email valide (255 caractères maximum).';
    }
    if (strlen($motDePasse) < 12) {
        $erreurs[] = 'Le mot de passe doit contenir au moins 12 caractères.';
    }
    if ($motDePasse !== $confirmation) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if (!$erreurs) {
        try {
            creerUtilisateur($bdd, $donnees, $motDePasse);
            $_SESSION['message_inscription'] = 'Votre compte a été créé. Vous pouvez maintenant vous connecter.';
            unset($_SESSION['jeton_csrf_inscription']);
            header('Location: index.php?uc=auth&action=login');
            exit;
        } catch (InvalidArgumentException $e) {
            $erreurs[] = $e->getMessage();
        } catch (PDOException $e) {
            error_log('Échec de la création d’un compte : ' . $e->getMessage());
            $erreurs[] = 'Une erreur est survenue pendant la création du compte. Veuillez réessayer.';
        } catch (RuntimeException $e) {
            error_log('Configuration de l’inscription indisponible : ' . $e->getMessage());
            $erreurs[] = 'L’inscription est momentanément indisponible. Veuillez réessayer plus tard.';
        }
    }
}

        include 'vues/v_inscription.php';
        break;

    case 'login':
    default:
        include 'vues/v_connexion.php';
        break;
}