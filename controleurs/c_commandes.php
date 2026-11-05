<?php
$role = $_SESSION['role'] ?? '';
switch ($role) {
    case 'client':
    case 'admin':
        if (!isset($_SESSION['email']) || !is_string($_SESSION['email'])) {
            http_response_code(403);
            exit('Connectez-vous pour consulter les commandes.');
        }
        break;

    default:
        http_response_code(403);
        exit('Connectez-vous pour consulter les commandes.');
}

$erreurCommandes = '';
$messageCommande = isset($_SESSION['message_commande']) && is_string($_SESSION['message_commande'])
    ? $_SESSION['message_commande']
    : '';
unset($_SESSION['message_commande']);
$commandes = [];

if ($role === 'client') {
    $_SESSION['jeton_csrf_commandes'] ??= bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jetonRecu = $_POST['jeton_csrf'] ?? '';
    if (
        $role !== 'client'
        || !is_string($jetonRecu)
        || !hash_equals($_SESSION['jeton_csrf_commandes'] ?? '', $jetonRecu)
    ) {
        http_response_code(400);
        $erreurCommandes = 'Le formulaire a expiré. Veuillez réessayer.';
    } elseif (($_POST['action'] ?? '') !== 'annuler') {
        http_response_code(400);
        $erreurCommandes = 'Cette action est invalide.';
    } else {
        $idCommande = filter_var($_POST['id_commande'] ?? null, FILTER_VALIDATE_INT);
        if ($idCommande === false || $idCommande < 1) {
            http_response_code(400);
            $erreurCommandes = 'Cette commande est invalide.';
        } else {
            try {
                $idUtilisateur = getIdUtilisateur($bdd, $_SESSION['email']);
                if (annulerCommande($bdd, $idCommande, $idUtilisateur)) {
                    $_SESSION['message_commande'] = 'Votre commande n°' . $idCommande . ' a été annulée.';
                    header('Location: index.php?uc=commandes');
                    exit;
                }

                $erreurCommandes = 'Cette commande ne peut pas être annulée.';
            } catch (PDOException $e) {
                error_log('Impossible d’annuler la commande : ' . $e->getMessage());
                http_response_code(500);
                $erreurCommandes = 'La commande est momentanément indisponible. Veuillez réessayer plus tard.';
            } catch (RuntimeException $e) {
                error_log('Impossible de préparer l’annulation de la commande : ' . $e->getMessage());
                http_response_code(500);
                $erreurCommandes = 'La commande est momentanément indisponible. Veuillez réessayer plus tard.';
            }
        }
    }
}

try {
    switch ($role) {
        case 'client':
            $idUtilisateur = getIdUtilisateur($bdd, $_SESSION['email']);
            break;

        case 'admin':
            $idUtilisateur = null;
            break;
    }

    $commandes = getCommandes($bdd, $idUtilisateur);

    $idsCommandes = [];
    foreach ($commandes as $commande) {
        $idsCommandes[] = (int) $commande['id'];
    }

    $lignesParCommande = getLignesCommandes($bdd, $idsCommandes);
    foreach ($commandes as $index => $commande) {
        $idCommande = (int) $commande['id'];
        $commandes[$index]['lignes'] = $lignesParCommande[$idCommande] ?? [];
    }
} catch (PDOException $e) {
    error_log('Impossible de charger les commandes : ' . $e->getMessage());
    http_response_code(500);
    $erreurCommandes = 'Les commandes sont momentanément indisponibles. Veuillez réessayer plus tard.';
} catch (RuntimeException $e) {
    error_log('Impossible de retrouver le compte client pour les commandes : ' . $e->getMessage());
    http_response_code(500);
    $erreurCommandes = 'Votre compte est momentanément indisponible. Veuillez réessayer plus tard.';
}

require __DIR__ . '/../vues/v_commandes.php';
