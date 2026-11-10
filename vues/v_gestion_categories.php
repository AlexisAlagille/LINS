<?php
$modeModificationCategorie ??= false;
$messageGestionCategorie ??= '';
$erreurGestionCategorie ??= '';
$libelleCategorie ??= '';
$categorieEnCours = is_array($categorieEnCours ?? null) ? $categorieEnCours : [];
$idCategorieEnCours = (int) ($categorieEnCours['id'] ?? 0);
$categories ??= [];

$echapper = static function (?string $valeur): string {
    return htmlspecialchars($valeur ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestion des catégories - LINS</title>
    <link rel="stylesheet" href="includes/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/v_header.php'; ?>
    <main class="page-gestion">
        <header class="catalogue-entete">
            <p class="catalogue-sur-titre">Espace gestionnaire</p>
            <h1><?= $modeModificationCategorie ? 'Modifier une catégorie' : 'Gestion des catégories' ?></h1>
            <p>Organisez les types de bois proposés dans le catalogue.</p>
        </header>

        <?php if ($messageGestionCategorie !== ''): ?>
            <p class="message message-succes" role="status"><?= $echapper($messageGestionCategorie) ?></p>
        <?php endif; ?>

        <?php if ($erreurGestionCategorie !== ''): ?>
            <p class="message message-erreur" role="alert"><?= $echapper($erreurGestionCategorie) ?></p>
        <?php endif; ?>

        <form method="post" action="index.php?uc=gestion_categories" class="formulaire-categorie">
            <input
                type="hidden"
                name="jeton_csrf"
                value="<?= $echapper($_SESSION['jeton_csrf_gestion_categories']) ?>"
            >
            <input type="hidden" name="action" value="<?= $modeModificationCategorie ? 'modifier' : 'ajouter' ?>">
            <?php if ($modeModificationCategorie): ?>
                <input type="hidden" name="id_categorie" value="<?= $idCategorieEnCours ?>">
            <?php endif; ?>

            <label class="champ champ-gestion">
                <span>Nom de la catégorie</span>
                <input
                    type="text"
                    name="libelle"
                    maxlength="50"
                    value="<?= $echapper($libelleCategorie) ?>"
                    required
                >
            </label>

            <div class="actions-gestion">
                <?php if ($modeModificationCategorie): ?>
                    <a class="lien-retour-gestion" href="index.php?uc=gestion_categories">Annuler</a>
                <?php else: ?>
                    <a class="lien-retour-gestion" href="index.php?uc=gestion_produits">Retour à la gestion du catalogue</a>
                <?php endif; ?>
                <button type="submit" class="bouton-inscription">
                    <?= $modeModificationCategorie ? 'Enregistrer la catégorie' : 'Ajouter la catégorie' ?>
                </button>
            </div>
        </form>

        <section class="gestion-liste-produits" aria-labelledby="titre-liste-categories">
            <h2 id="titre-liste-categories">Catégories existantes</h2>
            <?php if (!$categories): ?>
                <p class="catalogue-vide">Aucune catégorie n’a été créée.</p>
            <?php else: ?>
                <div class="table-produits-gestion">
                    <?php foreach ($categories as $categorie): ?>
                        <article class="ligne-categorie-gestion">
                            <h3><?= $echapper($categorie['libelle']) ?></h3>
                            <a
                                class="bouton-modifier-produit"
                                href="index.php?uc=gestion_categories&amp;id=<?= (int) $categorie['id'] ?>"
                            >
                                Modifier
                            </a>
                            <form
                                method="post"
                                action="index.php?uc=gestion_categories"
                                onsubmit="return confirm('Voulez-vous vraiment supprimer cette catégorie ?');"
                            >
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id_categorie" value="<?= (int) $categorie['id'] ?>">
                                <input
                                    type="hidden"
                                    name="jeton_csrf"
                                    value="<?= $echapper($_SESSION['jeton_csrf_gestion_categories']) ?>"
                                >
                                <button type="submit" class="bouton-supprimer-produit">Supprimer</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
                <nav
                    id="pagination-gestion-categories"
                    class="pagination-catalogue"
                    aria-label="Pages des catégories"
                    hidden
                ></nav>
            <?php endif; ?>
        </section>
    </main>
    <script>
        const listeCategoriesGestion = document.querySelector('.table-produits-gestion');

        if (listeCategoriesGestion) {
            const categories = Array.from(listeCategoriesGestion.querySelectorAll('.ligne-categorie-gestion'));
            const paginationCategories = document.querySelector('#pagination-gestion-categories');
            const categoriesParPage = 10;
            let pageCategories = 1;
            const nombrePages = Math.ceil(categories.length / categoriesParPage);

            function afficherPageCategories() {
                const debutPage = (pageCategories - 1) * categoriesParPage;
                categories.forEach((categorie, index) => {
                    categorie.hidden = index < debutPage || index >= debutPage + categoriesParPage;
                });

                paginationCategories.replaceChildren();
                paginationCategories.hidden = nombrePages <= 1;
                for (let numeroPage = 1; numeroPage <= nombrePages; numeroPage++) {
                    if (numeroPage === pageCategories) {
                        const pageActuelle = document.createElement('span');
                        pageActuelle.textContent = numeroPage;
                        pageActuelle.setAttribute('aria-current', 'page');
                        paginationCategories.append(pageActuelle);
                        continue;
                    }

                    const boutonPage = document.createElement('button');
                    boutonPage.type = 'button';
                    boutonPage.textContent = numeroPage;
                    boutonPage.setAttribute('aria-label', 'Page ' + numeroPage);
                    boutonPage.addEventListener('click', () => {
                        pageCategories = numeroPage;
                        afficherPageCategories();
                    });
                    paginationCategories.append(boutonPage);
                }
            }

            afficherPageCategories();
        }
    </script>
</body>
</html>
