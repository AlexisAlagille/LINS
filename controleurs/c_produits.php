<?php
$erreurCatalogue = '';
$jetonCsrfPanier = '';
$produitsParPage = 8;
$recherche = isset($_GET['recherche']) && is_string($_GET['recherche'])
    ? trim($_GET['recherche'])
    : '';
$idTypeBois = (int) ($_GET['type_bois'] ?? 0);
$page = (int) ($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}

switch ($_SESSION['role'] ?? '') {
    case 'client':
        $jetonCsrfPanier = $_SESSION['jeton_csrf_panier'] ??= bin2hex(random_bytes(32));
        break;
}

try {
    $typesBois = getTypesBois($bdd);
    $nombreProduits = compterProduits($bdd, $recherche, $idTypeBois);
    $nombrePages = max(1, (int) ceil($nombreProduits / $produitsParPage));
    if ($page > $nombrePages) {
        $page = $nombrePages;
    }

    $decalage = ($page - 1) * $produitsParPage;
    $produits = getTousLesProduits(
        $bdd,
        $produitsParPage,
        $decalage,
        $recherche,
        $idTypeBois
    );
} catch (PDOException $e) {
    error_log('Impossible de charger le catalogue : ' . $e->getMessage());
    http_response_code(500);
    $produits = [];
    $typesBois = [];
    $nombreProduits = 0;
    $nombrePages = 1;
    $erreurCatalogue = 'Le catalogue est momentanément indisponible. Veuillez réessayer plus tard.';
}

require __DIR__ . '/../vues/v_produits.php';
