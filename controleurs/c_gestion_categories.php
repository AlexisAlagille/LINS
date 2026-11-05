<?php
if (($_SESSION['role'] ?? '') !== 'gestionnaire') {
    http_response_code(403);
    exit('Cette page est réservée au gestionnaire.');
}

$_SESSION['jeton_csrf_gestion_categories'] ??= bin2hex(random_bytes(32));

$erreurGestionCategorie = '';
$messageGestionCategorie = isset($_SESSION['message_gestion_categorie'])
    && is_string($_SESSION['message_gestion_categorie'])
    ? $_SESSION['message_gestion_categorie']
    : '';
unset($_SESSION['message_gestion_categorie']);

$libelleCategorie = '';
$categorieEnCours = null;
$modeModificationCategorie = false;
$categories = [];

try {
    $idDemande = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if ($idDemande !== false && $idDemande !== null && $idDemande > 0) {
        $categorieEnCours = getTypeBois($bdd, $idDemande);
        if ($categorieEnCours === null) {
            http_response_code(404);
            $erreurGestionCategorie = 'Cette catégorie n’existe plus.';
        } else {
            $modeModificationCategorie = true;
            $libelleCategorie = $categorieEnCours['libelle'];
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $jetonRecu = $_POST['jeton_csrf'] ?? '';
        if (!is_string($jetonRecu) || !hash_equals($_SESSION['jeton_csrf_gestion_categories'], $jetonRecu)) {
            http_response_code(400);
            $erreurGestionCategorie = 'Le formulaire a expiré. Veuillez réessayer.';
        } else {
            $action = $_POST['action'] ?? '';
            $idCategorie = filter_var($_POST['id_categorie'] ?? null, FILTER_VALIDATE_INT);

            if ($action === 'supprimer') {
                if ($idCategorie === false || $idCategorie < 1) {
                    http_response_code(400);
                    $erreurGestionCategorie = 'Cette catégorie est invalide.';
                } elseif (supprimerTypeBois($bdd, $idCategorie)) {
                    $_SESSION['message_gestion_categorie'] = 'La catégorie a été supprimée.';
                    header('Location: index.php?uc=gestion_categories');
                    exit;
                } else {
                    $erreurGestionCategorie = 'Cette catégorie n’existe plus.';
                }
            } elseif ($action === 'ajouter' || $action === 'modifier') {
                $libelleCategorie = isset($_POST['libelle']) && is_string($_POST['libelle'])
                    ? trim($_POST['libelle'])
                    : '';

                if (
                    $libelleCategorie === ''
                    || !mb_check_encoding($libelleCategorie, 'UTF-8')
                    || mb_strlen($libelleCategorie, 'UTF-8') > 50
                ) {
                    $erreurGestionCategorie = 'Le nom est obligatoire et ne doit pas dépasser 50 caractères.';
                } elseif ($action === 'modifier' && ($idCategorie === false || $idCategorie < 1)) {
                    $erreurGestionCategorie = 'Cette catégorie est invalide.';
                } elseif ($action === 'modifier' && !modifierTypeBois($bdd, $idCategorie, $libelleCategorie)) {
                    $erreurGestionCategorie = 'Cette catégorie n’existe plus.';
                } else {
                    if ($action === 'ajouter') {
                        ajouterTypeBois($bdd, $libelleCategorie);
                        $_SESSION['message_gestion_categorie'] = 'La catégorie a été ajoutée.';
                    } else {
                        $_SESSION['message_gestion_categorie'] = 'La catégorie a été modifiée.';
                    }

                    header('Location: index.php?uc=gestion_categories');
                    exit;
                }
            } else {
                http_response_code(400);
                $erreurGestionCategorie = 'Cette action est invalide.';
            }
        }
    }

    $categories = getTypesBois($bdd);
} catch (InvalidArgumentException $e) {
    $erreurGestionCategorie = $e->getMessage();
    $categories = getTypesBois($bdd);
} catch (PDOException $e) {
    error_log('Impossible de gérer les catégories : ' . $e->getMessage());
    http_response_code(500);
    $erreurGestionCategorie = 'Les catégories n’ont pas pu être enregistrées. Vérifiez le nom et réessayez.';
    $categories = getTypesBois($bdd);
}

require __DIR__ . '/../vues/v_gestion_categories.php';
