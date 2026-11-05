<?php
require_once __DIR__ . '/../includes/modele/gestion_bdd.php';

$emailGestionnaire = $_SESSION['email'] ?? null;
if (
    !is_string($emailGestionnaire)
    || ($_SESSION['role'] ?? null) !== 'gestionnaire'
    || getUserRole($bdd, $emailGestionnaire) !== 'gestionnaire'
) {
    header('Location: index.php?uc=auth&action=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Méthode non autorisée.');
}

if (empty($_SESSION['jeton_csrf_gestion_commandes'])) {
    $_SESSION['jeton_csrf_gestion_commandes'] = bin2hex(random_bytes(32));
}

$erreurs = [];
$messageSucces = isset($_SESSION['message_gestion_commandes'])
    && is_string($_SESSION['message_gestion_commandes'])
    ? $_SESSION['message_gestion_commandes']
    : '';
unset($_SESSION['message_gestion_commandes']);
$commandes = [];
$statuts = [];
$statutFiltre = '';

try {
    $statuts = getStatutsCommande($bdd);
    $statutsAttendus = [
        1 => 'en attente',
        2 => 'validée',
        3 => 'expédiée',
        4 => 'refusée',
    ];
    $statutsParId = [];
    foreach ($statuts as $statut) {
        $statutsParId[(int) $statut['id']] = (string) $statut['libelle'];
    }
    foreach ($statutsAttendus as $idAttendu => $libelleAttendu) {
        if (($statutsParId[$idAttendu] ?? null) !== $libelleAttendu) {
            $erreurs[] = 'La configuration des statuts de commande est incomplète ou incorrecte.';
            break;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $jetonRecu = isset($_POST['jeton_csrf']) && is_string($_POST['jeton_csrf'])
            ? $_POST['jeton_csrf']
            : '';

        if (!hash_equals($_SESSION['jeton_csrf_gestion_commandes'], $jetonRecu)) {
            http_response_code(400);
            $erreurs[] = 'Le formulaire a expiré. Veuillez réessayer.';
        } else {
            $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
            $idCommande = isset($_POST['id_commande']) && is_string($_POST['id_commande'])
                ? filter_var($_POST['id_commande'], FILTER_VALIDATE_INT)
                : false;
            $idStatut = isset($_POST['id_statut']) && is_string($_POST['id_statut'])
                ? filter_var($_POST['id_statut'], FILTER_VALIDATE_INT)
                : false;

            if ($action !== 'modifier_statut' || $idCommande === false || $idCommande < 1
                || $idStatut === false || $idStatut < 1
            ) {
                http_response_code(400);
                $erreurs[] = 'Les données de la modification sont invalides.';
            } elseif (modifierStatutCommande($bdd, $idCommande, $idStatut)) {
                $_SESSION['message_gestion_commandes'] = 'Le statut de la commande a été mis à jour.';
                header('Location: index.php?uc=gestionCommandes');
                exit;
            } else {
                $erreurs[] = 'La commande sélectionnée n’existe plus.';
            }
        }
    }

    if (isset($_GET['statut_id']) && $_GET['statut_id'] !== '') {
        $statutFiltre = is_string($_GET['statut_id']) ? $_GET['statut_id'] : '';
        $idStatutFiltre = ctype_digit($statutFiltre) ? (int) $statutFiltre : 0;
        $idsStatuts = array_map(static fn (array $statut): int => (int) $statut['id'], $statuts);
        if (!in_array($idStatutFiltre, $idsStatuts, true)) {
            $erreurs[] = 'Le filtre de statut est invalide.';
            $statutFiltre = '';
        }
    } else {
        $idStatutFiltre = 0;
    }

    $commandes = getCommandes($bdd);
    if ($idStatutFiltre > 0) {
        $commandes = array_values(array_filter(
            $commandes,
            static fn (array $commande): bool => (int) $commande['id_statut'] === $idStatutFiltre
        ));
    }

    $idsCommandes = array_map(static fn (array $commande): int => (int) $commande['id'], $commandes);
    $lignesParCommande = getLignesCommandes($bdd, $idsCommandes);
    foreach ($commandes as $index => $commande) {
        $commandes[$index]['lignes'] = $lignesParCommande[(int) $commande['id']] ?? [];
    }
} catch (InvalidArgumentException $e) {
    $erreurs[] = $e->getMessage();
} catch (PDOException $e) {
    error_log('Impossible de charger ou modifier les commandes : ' . $e->getMessage());
    http_response_code(500);
    $erreurs[] = 'La gestion des commandes est momentanément indisponible.';
}

require __DIR__ . '/../vues/v_gestionCommandes.php';
