<?php
$PARAM_hote = 'localhost';
$PARAM_nom_bd = 'lins';
$PARAM_utilisateur = 'root';
$PARAM_mot_passe = '';

try {
    $bdd = new PDO(
        'mysql:host=' . $PARAM_hote . ';dbname=' . $PARAM_nom_bd . ';charset=utf8mb4',
        $PARAM_utilisateur,
        $PARAM_mot_passe,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Connexion à la base de données impossible : ' . $e->getMessage());
    http_response_code(500);
    exit('Le service est momentanément indisponible. Veuillez réessayer plus tard.');
}
?>
