<?php
if (($_SESSION['role'] ?? '') !== 'gestionnaire') {
    http_response_code(403);
    exit('Cette page est réservée au gestionnaire.');
}

$_SESSION['jeton_csrf_gestion_produits'] ??= bin2hex(random_bytes(32));

$erreurGestionProduit = '';
$messageGestionProduit = isset($_SESSION['message_gestion_produit'])
    && is_string($_SESSION['message_gestion_produit'])
    ? $_SESSION['message_gestion_produit']
    : '';
unset($_SESSION['message_gestion_produit']);

$donneesProduit = [
    'libelle' => '',
    'prix' => '',
    'description' => '',
    'id_type_bois' => '',
    'image_existante' => '',
];
$typesBois = [];
$imagesExistantes = [];
$dossierImages = __DIR__ . '/../includes/images/produits';

try {
    $typesBois = getTypesBois($bdd);

    $fichiersDuDossier = scandir($dossierImages);
    if ($fichiersDuDossier === false) {
        throw new RuntimeException('Impossible de lire le dossier des images du catalogue.');
    }

    foreach ($fichiersDuDossier as $nomFichier) {
        if (
            preg_match('/\.(jpe?g|png|webp)$/i', $nomFichier)
            && is_file($dossierImages . DIRECTORY_SEPARATOR . $nomFichier)
        ) {
            $imagesExistantes[] = $nomFichier;
        }
    }
    sort($imagesExistantes, SORT_NATURAL | SORT_FLAG_CASE);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $jetonRecu = $_POST['jeton_csrf'] ?? '';
        if (!is_string($jetonRecu) || !hash_equals($_SESSION['jeton_csrf_gestion_produits'], $jetonRecu)) {
            http_response_code(400);
            $erreurGestionProduit = 'Le formulaire a expiré. Veuillez réessayer.';
        } else {
            foreach ($donneesProduit as $champ => $valeur) {
                if (isset($_POST[$champ]) && is_string($_POST[$champ])) {
                    $donneesProduit[$champ] = trim($_POST[$champ]);
                }
            }

            $prix = filter_var($donneesProduit['prix'], FILTER_VALIDATE_FLOAT);
            $idTypeBois = filter_var($donneesProduit['id_type_bois'], FILTER_VALIDATE_INT);

            if (
                $donneesProduit['libelle'] === ''
                || !mb_check_encoding($donneesProduit['libelle'], 'UTF-8')
                || mb_strlen($donneesProduit['libelle'], 'UTF-8') > 150
            ) {
                $erreurGestionProduit = 'Le nom du produit est obligatoire et ne doit pas dépasser 150 caractères.';
            } elseif ($prix === false || $prix < 0 || $prix > 99999999.99) {
                $erreurGestionProduit = 'Saisissez un prix valide et positif.';
            } elseif ($idTypeBois === false || $idTypeBois < 1) {
                $erreurGestionProduit = 'Choisissez un type de bois.';
            } else {
                $image = '';
                $fichierTemporaire = null;
                $fichierImage = $_FILES['nouvelle_image'] ?? null;

                if (isset($_FILES['nouvelle_image']) && !is_array($fichierImage)) {
                    $erreurGestionProduit = 'Le fichier image reçu est invalide.';
                } elseif (
                    is_array($fichierImage)
                    && ($fichierImage['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
                ) {
                    $erreurUpload = $fichierImage['error'] ?? UPLOAD_ERR_NO_FILE;
                    if ($erreurUpload !== UPLOAD_ERR_OK) {
                        $erreurGestionProduit = $erreurUpload === UPLOAD_ERR_INI_SIZE
                            || $erreurUpload === UPLOAD_ERR_FORM_SIZE
                            ? 'L’image dépasse la taille maximale autorisée de 2 Mo.'
                            : 'Le téléversement de l’image a échoué.';
                    } elseif (($fichierImage['size'] ?? 0) > 2 * 1024 * 1024) {
                        $erreurGestionProduit = 'L’image dépasse la taille maximale autorisée de 2 Mo.';
                    } elseif (
                        !isset($fichierImage['tmp_name'])
                        || !is_string($fichierImage['tmp_name'])
                        || !is_uploaded_file($fichierImage['tmp_name'])
                    ) {
                        $erreurGestionProduit = 'Le fichier image reçu est invalide.';
                    } else {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $typeImage = $finfo->file($fichierImage['tmp_name']);
                        $extensionsImages = [
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/webp' => 'webp',
                        ];
                        $informationsImage = getimagesize($fichierImage['tmp_name']);

                        if (
                            !isset($extensionsImages[$typeImage])
                            || $informationsImage === false
                            || $informationsImage['mime'] !== $typeImage
                        ) {
                            $erreurGestionProduit = 'Choisissez une image JPEG, PNG ou WebP valide.';
                        } else {
                            $image = 'produit-' . bin2hex(random_bytes(12)) . '.' . $extensionsImages[$typeImage];
                            $cheminImage = $dossierImages . DIRECTORY_SEPARATOR . $image;
                            if (!move_uploaded_file($fichierImage['tmp_name'], $cheminImage)) {
                                $erreurGestionProduit = 'Impossible d’enregistrer la nouvelle image.';
                            } else {
                                $fichierTemporaire = $cheminImage;
                            }
                        }
                    }
                } elseif (in_array($donneesProduit['image_existante'], $imagesExistantes, true)) {
                    $image = $donneesProduit['image_existante'];
                } else {
                    $erreurGestionProduit = 'Choisissez une image existante ou ajoutez-en une nouvelle.';
                }

                if ($erreurGestionProduit === '') {
                    try {
                        ajouterProduit(
                            $bdd,
                            $donneesProduit['libelle'],
                            (float) $prix,
                            $donneesProduit['description'],
                            $image,
                            (int) $idTypeBois
                        );
                        $_SESSION['message_gestion_produit'] = 'Le produit a été ajouté au catalogue.';
                        header('Location: index.php?uc=gestion_produits');
                        exit;
                    } catch (Throwable $e) {
                        if ($fichierTemporaire !== null && is_file($fichierTemporaire)) {
                            unlink($fichierTemporaire);
                        }
                        throw $e;
                    }
                }
            }
        }
    }
} catch (InvalidArgumentException $e) {
    $erreurGestionProduit = $e->getMessage();
} catch (PDOException $e) {
    error_log('Impossible d’ajouter le produit : ' . $e->getMessage());
    http_response_code(500);
    $erreurGestionProduit = 'Le produit n’a pas pu être ajouté. Vérifiez les informations et réessayez.';
} catch (RuntimeException $e) {
    error_log('Erreur lors de l’ajout d’un produit : ' . $e->getMessage());
    http_response_code(500);
    $erreurGestionProduit = 'Le produit n’a pas pu être ajouté. Veuillez réessayer plus tard.';
}

require __DIR__ . '/../vues/v_gestion_produits.php';
