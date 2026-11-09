# LINS
Site internet pour la société fictive LINS (PRESBOIS) pour des élèves de spécialité SISR.

## Inscription

La page d'inscription est accessible à l'adresse `index.php?uc=auth`. Elle utilise la table
`user` et le rôle `client` définis dans la base `lins`. Avant de l'utiliser, appliquer
`database/migrations/20261103_email_unique_user.sql` afin d'imposer l'unicité des adresses
email en base de données. Le compte SQL configuré dans `includes/modele/connexion.php` doit
avoir les droits de lecture et d'écriture sur cette base.

La page d'administration des comptes utilise la colonne `user.date_creation`.
Appliquer également `database/migrations/20261105_user_date_creation.sql` à la base
avant de l'utiliser. Cette page est réservée au rôle `admin`.

Les nouveaux mots de passe sont stockés avec `password_hash()`. L'authentification devra
les vérifier avec `password_verify()`.

L'identifiant est généré à partir de la première lettre du nom suivie des six premières
lettres du prénom, en majuscules. Si cet identifiant existe déjà, un numéro est ajouté pour
le rendre unique.

## Panier et commandes

Le panier est conservé dans la session du client connecté. Pour activer l'enregistrement des
commandes, les tables `commande`, `ligne_commande` et `statut` doivent exister dans la base
`lins`. La migration `database/migrations/20261105_panier_commandes.sql` les crée si elles
n'existent pas. Une commande utilise le statut « en attente » ; son total est calculé à partir
des prix actuels du catalogue. Le client peut l’annuler tant qu’elle est « en attente »
ou « validée » ; l’annulation supprime la commande et ses lignes de la base. Elle n’est
plus possible une fois la commande expédiée. Le paiement et la livraison ne sont pas inclus.

Le rôle `gestionnaire` accède à `index.php?uc=gestionCommandes` pour consulter les commandes,
les filtrer par statut et mettre à jour leur statut parmi les quatre valeurs de la table
`statut`.

## Gestion du catalogue

Un compte ayant le rôle `gestionnaire` peut ouvrir « Gestion catalogue » depuis la navigation
pour ajouter ou modifier un produit. Il peut sélectionner une image JPEG, PNG ou WebP déjà
présente dans `includes/images/produits`, ou téléverser une nouvelle image de 2 Mo maximum.
La suppression d’un produit demande confirmation et est refusée si le produit figure déjà
dans une commande, afin de préserver l’historique.
Il peut aussi ajouter, modifier ou supprimer les catégories depuis « Gestion catégories ».
La suppression d’une catégorie est refusée tant qu’un produit l’utilise.
Pour choisir les produits affichés dans la section d’accueil, appliquer
`database/migrations/20261105_produits_mis_en_avant.sql`, puis utiliser les boutons
« Mettre en avant » de la gestion du catalogue. Jusqu’à six produits sélectionnés sont affichés.
