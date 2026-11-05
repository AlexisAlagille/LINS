<?php
if (($_SESSION['role'] ?? '') !== 'client' || !isset($_SESSION['email'])) {
    http_response_code(403);
    exit('Connectez-vous avec un compte client pour accéder au panier.');
}

$_SESSION['jeton_csrf_panier'] ??= bin2hex(random_bytes(32));
$_SESSION['panier'] ??= [];

$erreurPanier = '';
$messagePanier = $_SESSION['message_panier'] ?? '';
unset($_SESSION['message_panier']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jetonRecu = $_POST['jeton_csrf'] ?? '';

    if (!is_string($jetonRecu) || !hash_equals($_SESSION['jeton_csrf_panier'], $jetonRecu)) {
        http_response_code(400);
        $erreurPanier = 'Le formulaire a expiré. Veuillez réessayer.';
    } else {
        $action = $_POST['action'] ?? '';
        if (isset($_POST['retirer'])) {
            $action = 'retirer';
        }

        try {
            switch ($action) {
                case 'ajouter':
                    $idProduit = (int) ($_POST['id_produit'] ?? 0);
                    if (!getProduitsParIds($bdd, [$idProduit])) {
                        $erreurPanier = 'Ce produit n’est pas disponible.';
                        break;
                    }

                    $quantite = $_SESSION['panier'][$idProduit] ?? 0;
                    $_SESSION['panier'][$idProduit] = $quantite + 1;
                    $_SESSION['message_panier'] = 'Le produit a été ajouté au panier.';
                    break;

                case 'retirer':
                    unset($_SESSION['panier'][(int) ($_POST['retirer'] ?? 0)]);
                    $_SESSION['message_panier'] = 'Le produit a été retiré du panier.';
                    break;

                case 'continuer':
                    $saisies = $_POST['quantites'] ?? [];
                    if (!is_array($saisies)) {
                        $erreurPanier = 'Quantités invalides.';
                        break;
                    }

                    $nouvellesQuantites = [];
                    foreach ($_SESSION['panier'] as $id => $quantite) {
                        $nouvelleQuantite = (int) ($saisies[$id] ?? 0);
                        if ($nouvelleQuantite < 1) {
                            $erreurPanier = 'La quantité doit être au moins égale à 1.';
                            break;
                        }
                        $nouvellesQuantites[$id] = $nouvelleQuantite;
                    }

                    if ($erreurPanier === '') {
                        $_SESSION['panier'] = $nouvellesQuantites;
                        header('Location: index.php?uc=produits');
                        exit;
                    }
                    break;

                case 'commander':
                    if (!$_SESSION['panier']) {
                        $erreurPanier = 'Votre panier est vide.';
                        break;
                    }

                    $saisies = $_POST['quantites'] ?? [];
                    if (is_array($saisies)) {
                        $nouvellesQuantites = [];
                        foreach ($_SESSION['panier'] as $id => $quantite) {
                            $nouvelleQuantite = (int) ($saisies[$id] ?? $quantite);
                            if ($nouvelleQuantite < 1) {
                                $erreurPanier = 'La quantité doit être au moins égale à 1.';
                                break;
                            }
                            $nouvellesQuantites[$id] = $nouvelleQuantite;
                        }

                        if ($erreurPanier === '') {
                            $_SESSION['panier'] = $nouvellesQuantites;
                        }
                    }

                    if ($erreurPanier !== '') {
                        break;
                    }

                    $idClient = getIdUtilisateur($bdd, $_SESSION['email']);
                    $idCommande = creerCommande($bdd, $idClient, $_SESSION['panier']);
                    $_SESSION['panier'] = [];
                    $_SESSION['message_commande'] = 'Votre commande n°' . $idCommande . ' a bien été enregistrée.';
                    header('Location: index.php?uc=commandes');
                    exit;

                default:
                    $erreurPanier = 'Action inconnue.';
            }

            if ($erreurPanier === '') {
                header('Location: index.php?uc=panier');
                exit;
            }
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            $erreurPanier = $exception->getMessage();
        } catch (RuntimeException $exception) {
            error_log('Impossible de préparer la commande : ' . $exception->getMessage());
            http_response_code(500);
            $erreurPanier = 'La commande est momentanément indisponible.';
        } catch (PDOException $exception) {
            error_log('Erreur de base de données du panier : ' . $exception->getMessage());
            http_response_code(500);
            $erreurPanier = 'Le panier est momentanément indisponible.';
        }
    }
}

try {
    $idsProduits = array_map('intval', array_keys($_SESSION['panier']));
    $produitsPanier = getProduitsParIds($bdd, $idsProduits);

    foreach ($idsProduits as $idProduit) {
        if (!isset($produitsPanier[$idProduit])) {
            unset($_SESSION['panier'][$idProduit]);
            $messagePanier = 'Un produit indisponible a été retiré du panier.';
        }
    }
} catch (PDOException $exception) {
    error_log('Impossible de charger le panier : ' . $exception->getMessage());
    http_response_code(500);
    $produitsPanier = [];
    $erreurPanier = 'Le panier est momentanément indisponible.';
}

require __DIR__ . '/../vues/v_panier.php';
