# LINS
Site internet pour la société fictive LINS (PRESBOIS) pour des élèves de spécialité SISR.

## Inscription

La page d'inscription est accessible à l'adresse `index.php?uc=auth`. Elle utilise la table
`user` et le rôle `client` définis dans la base `lins`. Avant de l'utiliser, appliquer
`database/migrations/20261103_email_unique_user.sql` afin d'imposer l'unicité des adresses
email en base de données. Le compte SQL configuré dans `includes/modele/connexion.php` doit
avoir les droits de lecture et d'écriture sur cette base.

Les nouveaux mots de passe sont stockés avec `password_hash()`. L'authentification devra
les vérifier avec `password_verify()`.
