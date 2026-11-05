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
            <h1>Ajouter un produit</h1>
            <p>Complétez les informations du produit et choisissez son image.</p>
        </header>

        <?php if ($messageGestionProduit !== ''): ?>
            <p class="message message-succes" role="status"><?= $echapper($messageGestionProduit) ?></p>
        <?php endif; ?>

        <?php if ($erreurGestionProduit !== ''): ?>
            <p class="message message-erreur" role="alert"><?= $echapper($erreurGestionProduit) ?></p>
        <?php endif; ?>

        <form
            method="post"
            action="index.php?uc=gestion_produits"
            class="formulaire-gestion"
            enctype="multipart/form-data"
        >
            <input type="hidden" name="MAX_FILE_SIZE" value="2097152">
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
                <a class="lien-retour-gestion" href="index.php?uc=produits">Retour au catalogue</a>
                <button type="submit" class="bouton-inscription">Ajouter le produit</button>
            </div>
        </form>
    </main>
    <script>
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
