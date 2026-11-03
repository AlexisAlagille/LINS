<?php
require_once __DIR__ . '/../includes/modele/gestion_bdd.php';

$action = isset($_GET['action']) && is_string($_GET['action']) ? $_GET['action'] : 'login';
$inscriptionReussie = isset($_SESSION['message_inscription']);

if ($inscriptionReussie) {
    unset($_SESSION['message_inscription']);
}

switch ($action) {
    case 'logout':
        $jetonCsrf = isset($_POST['jeton_csrf']) && is_string($_POST['jeton_csrf'])
            ? $_POST['jeton_csrf']
            : '';

        if (
            $_SERVER['REQUEST_METHOD'] !== 'POST'
            || !isset($_SESSION['jeton_csrf_deconnexion'])
            || !is_string($_SESSION['jeton_csrf_deconnexion'])
            || !hash_equals($_SESSION['jeton_csrf_deconnexion'], $jetonCsrf)
        ) {
            http_response_code(400);
            exit('Requête de déconnexion invalide.');
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parametresCookie = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $parametresCookie['path'],
                $parametresCookie['domain'],
                $parametresCookie['secure'],
                $parametresCookie['httponly']
            );
        }
        session_destroy();
        header('Location: index.php?uc=auth&action=login');
        exit;

    case 'register':
        $erreurs = [];
        $donnees = [
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

            $motDePasse = isset($_POST['mot_de_passe']) && is_string($_POST['mot_de_passe'])
                ? $_POST['mot_de_passe']
                : '';
            $confirmation = isset($_POST['confirmation']) && is_string($_POST['confirmation'])
                ? $_POST['confirmation']
                : '';
            $jetonCsrf = isset($_POST['jeton_csrf']) && is_string($_POST['jeton_csrf'])
                ? $_POST['jeton_csrf']
                : '';

            if (!hash_equals($_SESSION['jeton_csrf_inscription'], $jetonCsrf)) {
                $erreurs[] = 'Le formulaire a expiré. Veuillez le soumettre à nouveau.';
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

        require __DIR__ . '/../vues/v_inscription.php';
        break;

    case 'verifier':
    case 'login':
    default:
        $erreurConnexion = '';
        $messageConnexion = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = isset($_POST['email']) && is_string($_POST['email'])
                ? trim($_POST['email'])
                : '';
            $motDePasse = isset($_POST['mot_de_passe']) && is_string($_POST['mot_de_passe'])
                ? $_POST['mot_de_passe']
                : (isset($_POST['motdepasse']) && is_string($_POST['motdepasse']) ? $_POST['motdepasse'] : '');

            if (
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false
                && $motDePasse !== ''
                && verifierConnexion($bdd, $email, $motDePasse)
            ) {
                $role = getUserRole($bdd, $email);
                if ($role === null) {
                    error_log('Connexion refusée : aucun rôle trouvé pour un compte authentifié.');
                    $erreurConnexion = 'Impossible de charger votre compte. Veuillez réessayer.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = $role;
                    header('Location: index.php?uc=accueil');
                    exit;
                }
            } else {
                $erreurConnexion = 'Adresse e-mail ou mot de passe incorrect.';
            }
        }

        require __DIR__ . '/../vues/v_connexion.php';
        break;
}