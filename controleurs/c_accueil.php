<?php
require_once __DIR__ . '/../includes/modele/gestion_bdd.php';

$produits = [];
$erreurCatalogue = '';

try {
	$produits = listerProduitsAccueil($bdd);
} catch (PDOException $e) {
	error_log('Impossible de charger le catalogue : ' . $e->getMessage());
	$erreurCatalogue = 'Le catalogue est momentanément indisponible.';
}

require __DIR__ . '/../vues/v_accueil.php';
