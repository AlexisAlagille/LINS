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
