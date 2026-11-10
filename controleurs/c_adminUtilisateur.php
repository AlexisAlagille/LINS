<?php
require_once __DIR__ . '/../includes/modele/gestion_bdd.php';

$emailAdministrateur = $_SESSION['email'] ?? null;
if (
    !is_string($emailAdministrateur)
    || ($_SESSION['role'] ?? null) !== 'admin'
    || getUserRole($bdd, $emailAdministrateur) !== 'admin'
) {
    header('Location: index.php?uc=auth&action=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Méthode non autorisée.');
}

if (empty($_SESSION['jeton_csrf_admin_utilisateurs'])) {
    $_SESSION['jeton_csrf_admin_utilisateurs'] = bin2hex(random_bytes(32));
}

$erreurs = [];
$messageSucces = $_SESSION['message_admin_utilisateurs'] ?? '';
unset($_SESSION['message_admin_utilisateurs']);
$donnees = ['nom' => '', 'prenom' => '', 'email' => ''];
$roleCreation = '';
$roles = [];
$utilisateurs = [];
$idCompteAdministrateur = 0;

try {
    $roles = listerRoles($bdd);
    $requeteCompteAdministrateur = $bdd->prepare('SELECT id FROM user WHERE email = ? LIMIT 1');
    $requeteCompteAdministrateur->execute([$emailAdministrateur]);
    $idCompteAdministrateur = $requeteCompteAdministrateur->fetchColumn();
    if ($idCompteAdministrateur === false) {
        unset($_SESSION['email'], $_SESSION['role']);
        header('Location: index.php?uc=auth&action=login');
        exit;
    }
    $idCompteAdministrateur = (int) $idCompteAdministrateur;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $jetonCsrf = isset($_POST['jeton_csrf']) && is_string($_POST['jeton_csrf'])
            ? $_POST['jeton_csrf']
            : '';
        if (!hash_equals($_SESSION['jeton_csrf_admin_utilisateurs'], $jetonCsrf)) {
            $erreurs[] = 'Le formulaire a expiré. Veuillez le soumettre à nouveau.';
        } else {
            $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
            if ($action === 'creer') {
                foreach ($donnees as $champ => $valeur) {
                    if (isset($_POST[$champ]) && is_string($_POST[$champ])) {
                        $donnees[$champ] = trim($_POST[$champ]);
                    }
                }
                $roleCreation = isset($_POST['role_id']) && is_string($_POST['role_id'])
                    ? $_POST['role_id']
                    : '';
                $motDePasse = isset($_POST['mot_de_passe']) && is_string($_POST['mot_de_passe'])
                    ? $_POST['mot_de_passe']
                    : '';
                $confirmation = isset($_POST['confirmation']) && is_string($_POST['confirmation'])
                    ? $_POST['confirmation']
                    : '';

                if ($donnees['nom'] === '' || strlen($donnees['nom']) > 100) {
                    $erreurs[] = 'Le nom est obligatoire et ne doit pas dépasser 100 caractères.';
                } elseif (preg_match('/^\p{L}[\p{L}\p{M}]*(?:-\p{L}[\p{L}\p{M}]*)*$/u', $donnees['nom']) !== 1) {
                    $erreurs[] = 'Le nom ne peut contenir que des lettres et des tirets entre les lettres.';
                }
                if ($donnees['prenom'] === '' || strlen($donnees['prenom']) > 100) {
                    $erreurs[] = 'Le prénom est obligatoire et ne doit pas dépasser 100 caractères.';
                } elseif (preg_match('/^\p{L}[\p{L}\p{M}]*$/u', $donnees['prenom']) !== 1) {
                    $erreurs[] = 'Le prénom ne peut contenir que des lettres.';
                }
                if (
                    $donnees['email'] === ''
                    || strlen($donnees['email']) > 255
                    || filter_var($donnees['email'], FILTER_VALIDATE_EMAIL) === false
                ) {
                    $erreurs[] = 'Veuillez saisir une adresse email valide (255 caractères maximum).';
                }
                if (strlen($motDePasse) < 12) {
                    $erreurs[] = 'Le mot de passe doit contenir au moins 12 caractères.';
                }
                if ($motDePasse !== $confirmation) {
                    $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
                }
                $idRoleCreation = ctype_digit($roleCreation) ? (int) $roleCreation : 0;
                $roleCreationSelectionne = null;
                foreach ($roles as $role) {
                    if ((int) $role['id'] === $idRoleCreation) {
                        $roleCreationSelectionne = $role;
                        break;
                    }
                }
                if (
                    $roleCreationSelectionne === null
                    || !in_array($roleCreationSelectionne['libelle'], ['admin', 'gestionnaire'], true)
                ) {
                    $erreurs[] = 'Seuls les comptes administrateur ou gestionnaire peuvent être créés ici.';
                }

                if (!$erreurs) {
                    creerUtilisateurAdmin($bdd, $donnees, $motDePasse, $idRoleCreation);
                    $_SESSION['message_admin_utilisateurs'] = 'Le compte a été créé.';
                    header('Location: index.php?uc=adminUtilisateurs');
                    exit;
                }
            } elseif ($action === 'modifier_role' || $action === 'supprimer') {
                $idCible = isset($_POST['utilisateur_id']) && is_string($_POST['utilisateur_id'])
                    && ctype_digit($_POST['utilisateur_id'])
                    ? (int) $_POST['utilisateur_id']
                    : 0;

                if ($idCible < 1) {
                    $erreurs[] = 'Le compte sélectionné est invalide.';
                } elseif ($idCible === $idCompteAdministrateur) {
                    $erreurs[] = 'Vous ne pouvez pas modifier ou supprimer votre propre compte.';
                } else {
                    $requeteCible = $bdd->prepare(
                        'SELECT role.libelle
                         FROM user
                         JOIN role ON role.id = user.id_role
                         WHERE user.id = ?
                         LIMIT 1'
                    );
                    $requeteCible->execute([$idCible]);
                    $roleCible = $requeteCible->fetchColumn();

                    if ($roleCible === false) {
                        $erreurs[] = 'Le compte sélectionné n’existe plus.';
                    } else {
                        $nombreAutresAdministrateurs = null;
                        if ($roleCible === 'admin') {
                            $requeteAutresAdministrateurs = $bdd->prepare(
                                'SELECT COUNT(*)
                                 FROM user
                                 JOIN role ON role.id = user.id_role
                                 WHERE role.libelle = ? AND user.id <> ?'
                            );
                            $requeteAutresAdministrateurs->execute(['admin', $idCible]);
                            $nombreAutresAdministrateurs = (int) $requeteAutresAdministrateurs->fetchColumn();
                        }

                        if ($nombreAutresAdministrateurs === 0) {
                            $erreurs[] = 'Le dernier compte administrateur ne peut pas être supprimé ou rétrogradé.';
                        } elseif ($action === 'modifier_role') {
                            $idRole = isset($_POST['role_id']) && is_string($_POST['role_id'])
                                && ctype_digit($_POST['role_id'])
                                ? (int) $_POST['role_id']
                                : 0;
                            $idsRoles = array_map(static fn (array $role): int => (int) $role['id'], $roles);
                            if (!in_array($idRole, $idsRoles, true)) {
                                $erreurs[] = 'Le rôle sélectionné est invalide.';
                            } else {
                                modifierRoleUtilisateur($bdd, $idCible, $idRole);
                                $_SESSION['message_admin_utilisateurs'] = 'Le rôle du compte a été modifié.';
                                header('Location: index.php?uc=adminUtilisateurs');
                                exit;
                            }
                        } else {
                            supprimerUtilisateur($bdd, $idCible);
                            $_SESSION['message_admin_utilisateurs'] = 'Le compte a été supprimé.';
                            header('Location: index.php?uc=adminUtilisateurs');
                            exit;
                        }
                    }
                }
            } else {
                $erreurs[] = 'Action non reconnue.';
            }
        }
    }

    $utilisateurs = listerUtilisateursAdmin($bdd);
} catch (InvalidArgumentException $e) {
    $erreurs[] = $e->getMessage();
    $utilisateurs = listerUtilisateursAdmin($bdd);
} catch (PDOException $e) {
    error_log('Échec de la gestion des comptes : ' . $e->getMessage());
    http_response_code(500);
    $erreurs[] = 'Une erreur est survenue pendant la gestion des comptes. Veuillez réessayer.';
    $roles = [];
    $utilisateurs = [];
} catch (RuntimeException $e) {
    error_log('Échec de la création d’un compte administrateur : ' . $e->getMessage());
    $erreurs[] = 'Une erreur est survenue pendant la création du compte. Veuillez réessayer.';
    $utilisateurs = listerUtilisateursAdmin($bdd);
}

require __DIR__ . '/../vues/v_adminUtilisateurs.php';
