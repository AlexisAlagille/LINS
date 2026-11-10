<?php
require_once __DIR__ . '/../includes/modele/gestion_bdd.php';

if (($_SESSION['role'] ?? null) !== 'client' || !isset($_SESSION['email']) || !is_string($_SESSION['email'])) {
    header('Location: index.php?uc=auth&action=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Méthode non autorisée.');
}

$emailActuel = $_SESSION['email'];
$erreurs = [];
$messageSucces = $_SESSION['message_mon_compte'] ?? '';
unset($_SESSION['message_mon_compte']);
$donnees = [
    'nom' => '',
    'prenom' => '',
    'email' => $emailActuel,
];

if (empty($_SESSION['jeton_csrf_mon_compte'])) {
    $_SESSION['jeton_csrf_mon_compte'] = bin2hex(random_bytes(32));
}

try {
    $utilisateur = obtenirInformationsUtilisateur($bdd, $emailActuel);
    if ($utilisateur === null) {
        unset($_SESSION['email'], $_SESSION['role']);
        header('Location: index.php?uc=auth&action=login');
        exit;
    }

    $donnees = [
        'nom' => (string) $utilisateur['nom'],
        'prenom' => (string) $utilisateur['prenom'],
        'email' => (string) $utilisateur['email'],
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($donnees as $champ => $valeur) {
            if (isset($_POST[$champ]) && is_string($_POST[$champ])) {
                $donnees[$champ] = trim($_POST[$champ]);
            }
        }

        $jetonCsrf = isset($_POST['jeton_csrf']) && is_string($_POST['jeton_csrf'])
            ? $_POST['jeton_csrf']
            : '';

        if (!hash_equals($_SESSION['jeton_csrf_mon_compte'], $jetonCsrf)) {
            $erreurs[] = 'Le formulaire a expiré. Veuillez le soumettre à nouveau.';
        }
        if ($donnees['nom'] === '' || strlen($donnees['nom']) > 100) {
            $erreurs[] = 'Le nom est obligatoire et ne doit pas dépasser 100 caractères.';
        } elseif (preg_match('/^\p{L}[\p{L}\p{M}]*(?:-\p{L}[\p{L}\p{M}]*)*$/u', $donnees['nom']) !== 1) {
            $erreurs[] = 'Le nom ne peut contenir que des lettres et des tirets entre les lettres.';
        }
        if ($donnees['prenom'] === '' || strlen($donnees['prenom']) > 100) {
            $erreurs[] = 'Le prénom est obligatoire et ne doit pas dépasser 100 caractères.';
        } elseif (preg_match('/^\p{L}[\p{L}\p{M}]*$/u', $donnees['prenom']) !== 1) {
            $erreurs[] = 'Le prénom ne peut contenir que des lettres.';
        }
        if (
            $donnees['email'] === ''
            || strlen($donnees['email']) > 255
            || filter_var($donnees['email'], FILTER_VALIDATE_EMAIL) === false
        ) {
            $erreurs[] = 'Veuillez saisir une adresse email valide (255 caractères maximum).';
        }

        if (!$erreurs) {
            modifierInformationsUtilisateur($bdd, $emailActuel, $donnees);
            $_SESSION['email'] = $donnees['email'];
            $_SESSION['message_mon_compte'] = 'Vos informations ont été mises à jour.';
            header('Location: index.php?uc=monCompte');
            exit;
        }
    }
} catch (InvalidArgumentException $e) {
    $erreurs[] = $e->getMessage();
} catch (PDOException $e) {
    error_log('Échec de la mise à jour des informations du compte : ' . $e->getMessage());
    $erreurs[] = 'Une erreur est survenue pendant la mise à jour de votre compte. Veuillez réessayer.';
}

require __DIR__ . '/../vues/v_monCompte.php';
