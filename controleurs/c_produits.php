<?php
$erreurCatalogue = '';
$jetonCsrfPanier = '';

switch ($_SESSION['role'] ?? '') {
    case 'client':
        $jetonCsrfPanier = $_SESSION['jeton_csrf_panier'] ??= bin2hex(random_bytes(32));
        break;
}

try {
    $produits = getTousLesProduits($bdd);
} catch (PDOException $e) {
    error_log('Impossible de charger le catalogue : ' . $e->getMessage());
    http_response_code(500);
    $produits = [];
    $erreurCatalogue = 'Le catalogue est momentanément indisponible. Veuillez réessayer plus tard.';
}

require __DIR__ . '/../vues/v_produits.php';
