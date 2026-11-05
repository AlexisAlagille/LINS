<?php
if (!in_array($_SESSION['role'] ?? '', ['admin', 'gestionnaire'], true)) {
    http_response_code(403);
    exit('Cette page est réservée à l’administration et au gestionnaire.');
}

$_SESSION['jeton_csrf_gestion_produits'] ??= bin2hex(random_bytes(32));
$repondreEnJson = static function (int $code, array $donnees): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($donnees, JSON_THROW_ON_ERROR);
    exit;
};
$requeteAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

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
$produitsGestion = [];
$produitEnCours = null;
$modeModification = false;
$dossierImages = __DIR__ . '/../includes/images/produits';

try {
    $typesBois = getTypesBois($bdd);
    $produitsGestion = getProduitsGestion($bdd);

    $idProduitDemande = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if ($idProduitDemande !== false && $idProduitDemande !== null && $idProduitDemande > 0) {
        $produitEnCours = getProduitGestion($bdd, $idProduitDemande);
        if ($produitEnCours === null) {
            http_response_code(404);
            $erreurGestionProduit = 'Ce produit n’existe plus.';
        } else {
            $modeModification = true;
            $donneesProduit = [
                'libelle' => $produitEnCours['libelle'],
                'prix' => (string) $produitEnCours['prix'],
                'description' => $produitEnCours['description'] ?? '',
                'id_type_bois' => (string) $produitEnCours['id_type_bois'],
                'image_existante' => $produitEnCours['image'],
            ];
        }
    }

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
            if ($requeteAjax) {
                $repondreEnJson(400, [
                    'succes' => false,
                    'message' => 'Le formulaire a expiré. Actualise la page et réessaie.',
                ]);
            }
            http_response_code(400);
            $erreurGestionProduit = 'Le formulaire a expiré. Veuillez réessayer.';
        } elseif (($_POST['action'] ?? '') === 'supprimer') {
            $idProduit = filter_var($_POST['id_produit'] ?? null, FILTER_VALIDATE_INT);
            if ($idProduit === false || $idProduit < 1) {
                http_response_code(400);
                $erreurGestionProduit = 'Ce produit est invalide.';
            } elseif (supprimerProduit($bdd, $idProduit)) {
                $_SESSION['message_gestion_produit'] = 'Le produit a été supprimé du catalogue.';
                header('Location: index.php?uc=gestion_produits');
                exit;
            } else {
                $erreurGestionProduit = 'Ce produit n’existe plus.';
            }
        } elseif (
            ($_POST['action'] ?? '') === 'mettre_en_avant'
            || ($_POST['action'] ?? '') === 'retirer_mise_en_avant'
        ) {
            $idProduit = filter_var($_POST['id_produit'] ?? null, FILTER_VALIDATE_INT);
            if ($idProduit === false || $idProduit < 1) {
                if ($requeteAjax) {
                    $repondreEnJson(400, ['succes' => false, 'message' => 'Ce produit est invalide.']);
                }
                http_response_code(400);
                $erreurGestionProduit = 'Ce produit est invalide.';
            } else {
                $misEnAvant = $_POST['action'] === 'mettre_en_avant';
                if (definirProduitMisEnAvant($bdd, $idProduit, $misEnAvant)) {
                    $message = $misEnAvant
                        ? 'Le produit apparaît maintenant sur la page d’accueil.'
                        : 'Le produit ne sera plus affiché sur la page d’accueil.';
                    if ($requeteAjax) {
                        $repondreEnJson(200, [
                            'succes' => true,
                            'mis_en_avant' => $misEnAvant,
                            'message' => $message,
                        ]);
                    }

                    $_SESSION['message_gestion_produit'] = $message;
                    header('Location: index.php?uc=gestion_produits');
                    exit;
                }

                if ($requeteAjax) {
                    $repondreEnJson(404, ['succes' => false, 'message' => 'Ce produit n’existe plus.']);
                }
                $erreurGestionProduit = 'Ce produit n’existe plus.';
            }
        } else {
            foreach ($donneesProduit as $champ => $valeur) {
                if (isset($_POST[$champ]) && is_string($_POST[$champ])) {
                    $donneesProduit[$champ] = trim($_POST[$champ]);
                }
            }

            $actionProduit = $_POST['action'] ?? '';
            $idProduit = filter_var($_POST['id_produit'] ?? null, FILTER_VALIDATE_INT);
            if ($actionProduit === 'modifier' && ($idProduit === false || $idProduit < 1)) {
                $erreurGestionProduit = 'Ce produit est invalide.';
            }

            $prix = filter_var($donneesProduit['prix'], FILTER_VALIDATE_FLOAT);
            $idTypeBois = filter_var($donneesProduit['id_type_bois'], FILTER_VALIDATE_INT);

            if (
                $erreurGestionProduit === ''
                && $actionProduit !== 'ajouter'
                && $actionProduit !== 'modifier'
            ) {
                $erreurGestionProduit = 'Cette action est invalide.';
            } elseif (
                $erreurGestionProduit === ''
                && (
                    $donneesProduit['libelle'] === ''
                    || !mb_check_encoding($donneesProduit['libelle'], 'UTF-8')
                    || mb_strlen($donneesProduit['libelle'], 'UTF-8') > 150
                )
            ) {
                $erreurGestionProduit = 'Le nom du produit est obligatoire et ne doit pas dépasser 150 caractères.';
            } elseif ($erreurGestionProduit === '' && ($prix === false || $prix < 0 || $prix > 99999999.99)) {
                $erreurGestionProduit = 'Saisissez un prix valide et positif.';
            } elseif ($erreurGestionProduit === '' && ($idTypeBois === false || $idTypeBois < 1)) {
                $erreurGestionProduit = 'Choisissez un type de bois.';
            }

            if ($erreurGestionProduit === '') {
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
                } elseif (
                    $actionProduit === 'modifier'
                    && $donneesProduit['image_existante'] === ''
                    && $produitEnCours !== null
                ) {
                    $image = $produitEnCours['image'];
                } elseif (in_array($donneesProduit['image_existante'], $imagesExistantes, true)) {
                    $image = $donneesProduit['image_existante'];
                } else {
                    $erreurGestionProduit = 'Choisissez une image existante ou ajoutez-en une nouvelle.';
                }

                if ($erreurGestionProduit === '') {
                    try {
                        if ($actionProduit === 'modifier') {
                            if (!modifierProduit(
                                $bdd,
                                (int) $idProduit,
                                $donneesProduit['libelle'],
                                (float) $prix,
                                $donneesProduit['description'],
                                $image,
                                (int) $idTypeBois
                            )) {
                                $erreurGestionProduit = 'Ce produit n’existe plus.';
                            } else {
                                $_SESSION['message_gestion_produit'] = 'Le produit a été modifié.';
                            }
                        } else {
                            ajouterProduit(
                                $bdd,
                                $donneesProduit['libelle'],
                                (float) $prix,
                                $donneesProduit['description'],
                                $image,
                                (int) $idTypeBois
                            );
                            $_SESSION['message_gestion_produit'] = 'Le produit a été ajouté au catalogue.';
                        }

                        if ($erreurGestionProduit === '') {
                            header('Location: index.php?uc=gestion_produits');
                            exit;
                        }
                        if ($fichierTemporaire !== null && is_file($fichierTemporaire)) {
                            unlink($fichierTemporaire);
                        }
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
    if ($requeteAjax) {
        $repondreEnJson(400, ['succes' => false, 'message' => $e->getMessage()]);
    }
    $erreurGestionProduit = $e->getMessage();
} catch (PDOException $e) {
    error_log('Impossible d’enregistrer le produit : ' . $e->getMessage());
    if ($requeteAjax) {
        $repondreEnJson(500, [
            'succes' => false,
            'message' => 'La mise à jour est momentanément indisponible. Réessaie plus tard.',
        ]);
    }
    http_response_code(500);
    $erreurGestionProduit = 'Le produit n’a pas pu être enregistré. Vérifiez les informations et réessayez.';
} catch (RuntimeException $e) {
    error_log('Erreur lors de la gestion d’un produit : ' . $e->getMessage());
    if ($requeteAjax) {
        $repondreEnJson(500, [
            'succes' => false,
            'message' => 'La mise à jour est momentanément indisponible. Réessaie plus tard.',
        ]);
    }
    http_response_code(500);
    $erreurGestionProduit = 'Le produit n’a pas pu être enregistré. Veuillez réessayer plus tard.';
}

require __DIR__ . '/../vues/v_gestion_produits.php';
