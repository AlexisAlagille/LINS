<?php
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

        <form
            method="post"
            action="index.php?uc=gestion_produits<?= $modeModification ? '&amp;id=' . (int) $produitEnCours['id'] : '' ?>"
            class="formulaire-gestion"
            enctype="multipart/form-data"
        >
            <input type="hidden" name="MAX_FILE_SIZE" value="2097152">
            <input type="hidden" name="action" value="<?= $modeModification ? 'modifier' : 'ajouter' ?>">
            <?php if ($modeModification): ?>
                <input type="hidden" name="id_produit" value="<?= (int) $produitEnCours['id'] ?>">
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

        <section class="gestion-liste-produits" aria-labelledby="titre-liste-produits">
            <h2 id="titre-liste-produits">Produits du catalogue</h2>
            <?php if (!$produitsGestion): ?>
                <p class="catalogue-vide">Aucun produit dans le catalogue.</p>
            <?php else: ?>
                <div class="table-produits-gestion">
                    <?php foreach ($produitsGestion as $produit): ?>
                        <article class="ligne-produit-gestion">
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
            <?php endif; ?>
        </section>
    </main>
    <script>
        const listeProduitsGestion = document.querySelector('.table-produits-gestion');

        if (listeProduitsGestion) {
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
                    formulaire.querySelector('[name="action"]').value = misEnAvant
                        ? 'retirer_mise_en_avant'
                        : 'mettre_en_avant';
                    bouton.textContent = misEnAvant ? 'Retirer de l’accueil' : 'Mettre en avant';
                    formulaire.closest('.ligne-produit-gestion')
                        .querySelector('.statut-mise-en-avant').textContent = misEnAvant
                            ? 'Mis en avant sur l’accueil'
                            : 'Non mis en avant';
                    retour.textContent = '';
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
