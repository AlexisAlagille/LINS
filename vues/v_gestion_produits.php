<?php
$modeModification ??= false;
$messageGestionProduit ??= '';
$erreurGestionProduit ??= '';
$produitEnCours = is_array($produitEnCours ?? null) ? $produitEnCours : [];
$idProduitEnCours = (int) ($produitEnCours['id'] ?? 0);
$donneesProduit ??= [
    'libelle' => '',
    'prix' => '',
    'description' => '',
    'id_type_bois' => '',
    'image_existante' => '',
];
$typesBois ??= [];
$imagesExistantes ??= [];
$produitsGestion ??= [];

$echapper = static function (?string $valeur): string {
    return htmlspecialchars($valeur ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestion du catalogue - LINS</title>
    <link rel="stylesheet" href="includes/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/v_header.php'; ?>
    <main class="page-gestion">
        <header class="catalogue-entete">
            <p class="catalogue-sur-titre">Espace gestionnaire</p>
            <h1><?= $modeModification ? 'Modifier un produit' : 'Gestion du catalogue' ?></h1>
            <p>
                <?= $modeModification
                    ? 'Modifiez les informations du produit sélectionné.'
                    : 'Ajoutez, modifiez ou supprimez les produits du catalogue.' ?>
            </p>
        </header>

        <?php if ($messageGestionProduit !== ''): ?>
            <p class="message message-succes" role="status"><?= $echapper($messageGestionProduit) ?></p>
        <?php endif; ?>

        <?php if ($erreurGestionProduit !== ''): ?>
            <p class="message message-erreur" role="alert"><?= $echapper($erreurGestionProduit) ?></p>
        <?php endif; ?>

        <details
            class="panneau-formulaire-produit"
            <?= $modeModification || $erreurGestionProduit !== '' ? 'open' : '' ?>
        >
            <summary>
                <?= $modeModification ? 'Modifier le produit' : 'Ajouter un produit' ?>
            </summary>
        <form
            method="post"
            action="index.php?uc=gestion_produits<?= $modeModification ? '&amp;id=' . $idProduitEnCours : '' ?>"
            class="formulaire-gestion"
            enctype="multipart/form-data"
        >
            <input type="hidden" name="MAX_FILE_SIZE" value="2097152">
            <input type="hidden" name="action" value="<?= $modeModification ? 'modifier' : 'ajouter' ?>">
            <?php if ($modeModification): ?>
                <input type="hidden" name="id_produit" value="<?= $idProduitEnCours ?>">
            <?php endif; ?>
            <input
                type="hidden"
                name="jeton_csrf"
                value="<?= $echapper($_SESSION['jeton_csrf_gestion_produits']) ?>"
            >

            <section class="informations-produit">
                <h2>Informations du produit</h2>
                <label class="champ champ-gestion">
                    <span>Nom du produit</span>
                    <input
                        type="text"
                        name="libelle"
                        maxlength="150"
                        value="<?= $echapper($donneesProduit['libelle']) ?>"
                        required
                    >
                </label>

                <div class="champs-produit">
                    <label class="champ champ-gestion">
                        <span>Prix (€)</span>
                        <input
                            type="number"
                            name="prix"
                            min="0"
                            step="0.01"
                            value="<?= $echapper($donneesProduit['prix']) ?>"
                            required
                        >
                    </label>

                    <label class="champ champ-gestion">
                        <span>Type de bois</span>
                        <select name="id_type_bois" required>
                            <option value="">Choisir un type</option>
                            <?php foreach ($typesBois as $type): ?>
                                <option
                                    value="<?= (int) $type['id'] ?>"
                                    <?= $donneesProduit['id_type_bois'] === (string) $type['id'] ? 'selected' : '' ?>
                                >
                                    <?= $echapper($type['libelle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <label class="champ champ-gestion">
                    <span>Description</span>
                    <textarea name="description" rows="6"><?= $echapper($donneesProduit['description']) ?></textarea>
                </label>
            </section>

            <section class="images-produit">
                <h2>Image du produit</h2>
                <label class="champ champ-gestion">
                    <span>Utiliser une image existante</span>
                    <select name="image_existante">
                        <option value="">Choisir une image</option>
                        <?php foreach ($imagesExistantes as $image): ?>
                            <option
                                value="<?= $echapper($image) ?>"
                                <?= $donneesProduit['image_existante'] === $image ? 'selected' : '' ?>
                            >
                                <?= $echapper($image) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="champ champ-gestion">
                    <span>Ou ajouter une nouvelle image</span>
                    <input
                        type="file"
                        name="nouvelle_image"
                        accept="image/jpeg,image/png,image/webp"
                    >
                    <small>JPEG, PNG ou WebP, 2 Mo maximum.</small>
                </label>

                <figure class="apercu-image-produit">
                    <figcaption>Aperçu</figcaption>
                    <img
                        id="apercu-image"
                        src="<?= in_array($donneesProduit['image_existante'], $imagesExistantes, true)
                            ? 'includes/images/produits/' . $echapper($donneesProduit['image_existante'])
                            : '' ?>"
                        alt="Aperçu de l’image choisie pour le produit"
                        <?= in_array($donneesProduit['image_existante'], $imagesExistantes, true) ? '' : 'hidden' ?>
                    >
                    <p id="message-apercu-image" <?= in_array($donneesProduit['image_existante'], $imagesExistantes, true) ? 'hidden' : '' ?>>
                        Sélectionnez une image.
                    </p>
                </figure>
            </section>

            <div class="actions-gestion">
                <?php if ($modeModification): ?>
                    <a class="lien-retour-gestion" href="index.php?uc=gestion_produits">Annuler la modification</a>
                <?php else: ?>
                    <a class="lien-retour-gestion" href="index.php?uc=produits">Retour au catalogue</a>
                <?php endif; ?>
                <button type="submit" class="bouton-inscription">
                    <?= $modeModification ? 'Enregistrer les modifications' : 'Ajouter le produit' ?>
                </button>
            </div>
        </form>
        </details>

        <section class="gestion-liste-produits" aria-labelledby="titre-liste-produits">
            <h2 id="titre-liste-produits">Produits du catalogue</h2>
            <?php if (!$produitsGestion): ?>
                <p class="catalogue-vide">Aucun produit dans le catalogue.</p>
            <?php else: ?>
                <form class="filtres-catalogue filtres-gestion-produits" id="filtres-gestion-produits">
                    <div class="filtre-recherche">
                        <label for="recherche-gestion-produit">Rechercher un produit</label>
                        <div class="champ-recherche">
                            <span aria-hidden="true">⌕</span>
                            <input
                                id="recherche-gestion-produit"
                                type="search"
                                placeholder="Nom ou description"
                                autocomplete="off"
                            >
                        </div>
                    </div>

                    <div class="filtre-type">
                        <label for="type-gestion-produit">Type de bois</label>
                        <select id="type-gestion-produit">
                            <option value="0">Tous les types</option>
                            <?php foreach ($typesBois as $type): ?>
                                <option value="<?= (int) $type['id'] ?>">
                                    <?= $echapper($type['libelle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filtre-mise-en-avant">
                        <label for="mise-en-avant-gestion-produit">Mise en avant</label>
                        <select id="mise-en-avant-gestion-produit">
                            <option value="tous">Tous les produits</option>
                            <option value="oui">Mis en avant</option>
                            <option value="non">Non mis en avant</option>
                        </select>
                    </div>
                </form>

                <p id="compte-gestion-produits" class="catalogue-compte" role="status" aria-live="polite">
                    <?= count($produitsGestion) ?> produit<?= count($produitsGestion) > 1 ? 's' : '' ?>
                </p>
                <div class="table-produits-gestion">
                    <?php foreach ($produitsGestion as $produit): ?>
                        <article
                            class="ligne-produit-gestion"
                            data-recherche="<?= $echapper($produit['libelle'] . ' ' . ($produit['description'] ?? '')) ?>"
                            data-type-bois="<?= (int) $produit['id_type_bois'] ?>"
                            data-mis-en-avant="<?= (int) $produit['mis_en_avant'] === 1 ? 'oui' : 'non' ?>"
                        >
                            <img
                                src="includes/images/produits/<?= $echapper(basename($produit['image'])) ?>"
                                alt=""
                                loading="lazy"
                            >
                            <div class="details-produit-gestion">
                                <h3><?= $echapper($produit['libelle']) ?></h3>
                                <p>
                                    <?= $echapper($produit['type_bois']) ?>
                                    · <?= number_format((float) $produit['prix'], 2, ',', ' ') ?>&nbsp;€
                                </p>
                                <p class="statut-mise-en-avant">
                                    <?= (int) $produit['mis_en_avant'] === 1 ? 'Mis en avant sur l’accueil' : 'Non mis en avant' ?>
                                </p>
                            </div>
                            <form method="post" action="index.php?uc=gestion_produits" class="form-mise-en-avant">
                                <input
                                    type="hidden"
                                    name="action"
                                    value="<?= (int) $produit['mis_en_avant'] === 1 ? 'retirer_mise_en_avant' : 'mettre_en_avant' ?>"
                                >
                                <input type="hidden" name="id_produit" value="<?= (int) $produit['id'] ?>">
                                <input
                                    type="hidden"
                                    name="jeton_csrf"
                                    value="<?= $echapper($_SESSION['jeton_csrf_gestion_produits']) ?>"
                                >
                                <button type="submit" class="bouton-mise-en-avant">
                                    <?= (int) $produit['mis_en_avant'] === 1 ? 'Retirer de l’accueil' : 'Mettre en avant' ?>
                                </button>
                                <span class="retour-mise-en-avant" role="status" aria-live="polite"></span>
                            </form>
                            <a
                                class="bouton-modifier-produit"
                                href="index.php?uc=gestion_produits&amp;id=<?= (int) $produit['id'] ?>"
                            >
                                Modifier
                            </a>
                            <form
                                method="post"
                                action="index.php?uc=gestion_produits"
                                class="form-suppression-produit"
                                onsubmit="return confirm('Voulez-vous vraiment supprimer ce produit ?');"
                            >
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id_produit" value="<?= (int) $produit['id'] ?>">
                                <input
                                    type="hidden"
                                    name="jeton_csrf"
                                    value="<?= $echapper($_SESSION['jeton_csrf_gestion_produits']) ?>"
                                >
                                <button type="submit" class="bouton-supprimer-produit">Supprimer</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
                <nav
                    id="pagination-gestion-produits"
                    class="pagination-catalogue"
                    aria-label="Pages des produits"
                    hidden
                ></nav>
                <p id="aucun-produit-filtre" class="catalogue-vide" hidden>
                    Aucun produit ne correspond à ces critères.
                </p>
            <?php endif; ?>
        </section>
    </main>
    <script>
        const panneauFormulaireProduit = document.querySelector('.panneau-formulaire-produit');

        if (panneauFormulaireProduit) {
            const resumeFormulaireProduit = panneauFormulaireProduit.querySelector('summary');
            let animationFormulaireEnCours = false;

            resumeFormulaireProduit.addEventListener('click', async (evenement) => {
                evenement.preventDefault();
                if (animationFormulaireEnCours) {
                    return;
                }

                animationFormulaireEnCours = true;
                const duree = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 1 : 320;
                const ouverture = !panneauFormulaireProduit.open;
                panneauFormulaireProduit.style.overflow = 'hidden';

                if (ouverture) {
                    const hauteurFermee = resumeFormulaireProduit.offsetHeight;
                    panneauFormulaireProduit.open = true;
                    const hauteurOuverte = panneauFormulaireProduit.offsetHeight;
                    const animationPanneau = panneauFormulaireProduit.animate(
                        [{ height: hauteurFermee + 'px' }, { height: hauteurOuverte + 'px' }],
                        { duration: duree, easing: 'cubic-bezier(0.2, 0.7, 0.25, 1)' }
                    );

                    await animationPanneau.finished;
                    animationPanneau.cancel();
                } else {
                    const hauteurOuverte = panneauFormulaireProduit.offsetHeight;
                    const hauteurFermee = resumeFormulaireProduit.offsetHeight;
                    const animationPanneau = panneauFormulaireProduit.animate(
                        [{ height: hauteurOuverte + 'px' }, { height: hauteurFermee + 'px' }],
                        { duration: duree, easing: 'cubic-bezier(0.2, 0.7, 0.25, 1)' }
                    );

                    await animationPanneau.finished;
                    panneauFormulaireProduit.open = false;
                    animationPanneau.cancel();
                }

                panneauFormulaireProduit.style.overflow = '';
                animationFormulaireEnCours = false;
            });
        }

        const listeProduitsGestion = document.querySelector('.table-produits-gestion');

        if (listeProduitsGestion) {
            const rechercheGestionProduits = document.querySelector('#recherche-gestion-produit');
            const filtreTypeGestionProduits = document.querySelector('#type-gestion-produit');
            const filtreMiseEnAvant = document.querySelector('#mise-en-avant-gestion-produit');
            const compteGestionProduits = document.querySelector('#compte-gestion-produits');
            const aucunProduitFiltre = document.querySelector('#aucun-produit-filtre');
            const paginationProduits = document.querySelector('#pagination-gestion-produits');
            const produitsParPage = 10;
            let pageProduits = 1;

            function afficherPaginationProduits(nombrePages) {
                paginationProduits.replaceChildren();
                paginationProduits.hidden = nombrePages <= 1;

                if (nombrePages <= 1) {
                    return;
                }

                for (let numeroPage = 1; numeroPage <= nombrePages; numeroPage++) {
                    if (numeroPage === pageProduits) {
                        const pageActuelle = document.createElement('span');
                        pageActuelle.textContent = numeroPage;
                        pageActuelle.setAttribute('aria-current', 'page');
                        paginationProduits.append(pageActuelle);
                        continue;
                    }

                    const boutonPage = document.createElement('button');
                    boutonPage.type = 'button';
                    boutonPage.textContent = numeroPage;
                    boutonPage.setAttribute('aria-label', 'Page ' + numeroPage);
                    boutonPage.addEventListener('click', () => {
                        pageProduits = numeroPage;
                        filtrerProduitsGestion();
                    });
                    paginationProduits.append(boutonPage);
                }
            }

            function filtrerProduitsGestion() {
                const recherche = rechercheGestionProduits.value.trim().toLocaleLowerCase('fr');
                const typeBois = filtreTypeGestionProduits.value;
                const miseEnAvant = filtreMiseEnAvant.value;
                const produitsCorrespondants = [];

                listeProduitsGestion.querySelectorAll('.ligne-produit-gestion').forEach((produit) => {
                    const correspondRecherche = produit.dataset.recherche
                        .toLocaleLowerCase('fr').includes(recherche);
                    const correspondType = typeBois === '0' || produit.dataset.typeBois === typeBois;
                    const correspondMiseEnAvant = miseEnAvant === 'tous'
                        || produit.dataset.misEnAvant === miseEnAvant;

                    if (correspondRecherche && correspondType && correspondMiseEnAvant) {
                        produitsCorrespondants.push(produit);
                    }
                });

                const nombrePages = Math.ceil(produitsCorrespondants.length / produitsParPage);
                pageProduits = Math.min(pageProduits, Math.max(nombrePages, 1));
                const debutPage = (pageProduits - 1) * produitsParPage;
                const produitsDeLaPage = new Set(
                    produitsCorrespondants.slice(debutPage, debutPage + produitsParPage)
                );

                listeProduitsGestion.querySelectorAll('.ligne-produit-gestion').forEach((produit) => {
                    produit.hidden = !produitsDeLaPage.has(produit);
                });

                compteGestionProduits.textContent = produitsCorrespondants.length
                    + ' produit' + (produitsCorrespondants.length > 1 ? 's' : '');
                aucunProduitFiltre.hidden = produitsCorrespondants.length !== 0;
                afficherPaginationProduits(nombrePages);
            }

            rechercheGestionProduits.addEventListener('input', () => {
                pageProduits = 1;
                filtrerProduitsGestion();
            });
            filtreTypeGestionProduits.addEventListener('change', () => {
                pageProduits = 1;
                filtrerProduitsGestion();
            });
            filtreMiseEnAvant.addEventListener('change', () => {
                pageProduits = 1;
                filtrerProduitsGestion();
            });
            filtrerProduitsGestion();

            listeProduitsGestion.addEventListener('submit', async (evenement) => {
                const formulaire = evenement.target.closest('.form-mise-en-avant');
                if (!formulaire) {
                    return;
                }

                evenement.preventDefault();
                const bouton = formulaire.querySelector('button');
                const retour = formulaire.querySelector('.retour-mise-en-avant');
                bouton.disabled = true;
                retour.textContent = 'Mise à jour…';

                try {
                    const urlFormulaire = new URL(formulaire.getAttribute('action'), window.location.href);
                    const reponse = await fetch(urlFormulaire, {
                        method: 'POST',
                        body: new FormData(formulaire),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    let resultat;
                    try {
                        resultat = await reponse.json();
                    } catch {
                        throw new Error('La réponse du serveur est invalide. Réessaie.');
                    }

                    if (!reponse.ok || !resultat.succes) {
                        throw new Error(resultat.message || 'La mise à jour a échoué.');
                    }

                    const misEnAvant = resultat.mis_en_avant;
                    const ligneProduit = formulaire.closest('.ligne-produit-gestion');
                    formulaire.querySelector('[name="action"]').value = misEnAvant
                        ? 'retirer_mise_en_avant'
                        : 'mettre_en_avant';
                    bouton.textContent = misEnAvant ? 'Retirer de l’accueil' : 'Mettre en avant';
                    ligneProduit.dataset.misEnAvant = misEnAvant ? 'oui' : 'non';
                    ligneProduit.querySelector('.statut-mise-en-avant').textContent = misEnAvant
                        ? 'Mis en avant sur l’accueil'
                        : 'Non mis en avant';
                    retour.textContent = '';
                    filtrerProduitsGestion();
                } catch (erreur) {
                    retour.textContent = erreur.message || 'La mise à jour a échoué. Réessaie.';
                } finally {
                    bouton.disabled = false;
                }
            });
        }

        const choixImageExistante = document.querySelector('[name="image_existante"]');
        const choixNouvelleImage = document.querySelector('[name="nouvelle_image"]');
        const apercuImage = document.querySelector('#apercu-image');
        const messageApercuImage = document.querySelector('#message-apercu-image');
        let urlImageTemporaire = null;

        function afficherApercuImage(url) {
            apercuImage.src = url;
            apercuImage.hidden = false;
            messageApercuImage.hidden = true;
        }

        function afficherImageExistante() {
            if (urlImageTemporaire !== null) {
                URL.revokeObjectURL(urlImageTemporaire);
                urlImageTemporaire = null;
            }

            if (choixImageExistante.value !== '') {
                afficherApercuImage(
                    'includes/images/produits/' + encodeURIComponent(choixImageExistante.value)
                );
            } else {
                apercuImage.removeAttribute('src');
                apercuImage.hidden = true;
                messageApercuImage.hidden = false;
            }
        }

        choixImageExistante.addEventListener('change', () => {
            choixNouvelleImage.value = '';
            afficherImageExistante();
        });

        choixNouvelleImage.addEventListener('change', () => {
            const fichier = choixNouvelleImage.files[0];
            if (!fichier) {
                afficherImageExistante();
                return;
            }

            if (urlImageTemporaire !== null) {
                URL.revokeObjectURL(urlImageTemporaire);
            }
            urlImageTemporaire = URL.createObjectURL(fichier);
            afficherApercuImage(urlImageTemporaire);
        });

        window.addEventListener('pagehide', () => {
            if (urlImageTemporaire !== null) {
                URL.revokeObjectURL(urlImageTemporaire);
            }
        });
    </script>
</body>
</html>
