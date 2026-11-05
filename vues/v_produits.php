<?php
/** @var list<array{id: int|string, libelle: string, prix: string, description: ?string, image: string, type_bois: ?string}> $produits */
/** @var string $erreurCatalogue */
/** @var string $jetonCsrfPanier */
/** @var int $page */
/** @var int $nombrePages */
/** @var int $nombreProduits */
/** @var string $recherche */
/** @var int $idTypeBois */
/** @var list<array{id: int|string, libelle: string}> $typesBois */

$role = $_SESSION['role'] ?? '';

$echapper = static function (?string $valeur): string {
    return htmlspecialchars($valeur ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nos produits - LINS</title>
    <link rel="stylesheet" href="includes/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/v_header.php'; ?>
    <main class="catalogue">
        <header class="catalogue-entete">
            <p class="catalogue-sur-titre">La sélection LINS</p>

            <h1>Nos produits</h1>

            <p>Découvrez notre gamme de panneaux et de matériaux bois.</p>
        </header>

        <form method="get" action="index.php" class="filtres-catalogue" id="filtres-catalogue">
            <input type="hidden" name="uc" value="produits">

            <div class="filtre-recherche">
                <label for="recherche-produit">Rechercher dans le catalogue</label>
                <div class="champ-recherche">
                    <span aria-hidden="true">⌕</span>
                    <input
                        id="recherche-produit"
                        type="search"
                        name="recherche"
                        value="<?= $echapper($recherche) ?>"
                        placeholder="Ex. panneau MDF hydrofuge"
                        autocomplete="off"
                    >
                </div>
                <small>Nom ou description du produit</small>
            </div>

            <div class="filtre-type">
                <label for="type-bois">Type de bois</label>
                <select id="type-bois" name="type_bois">
                    <option value="0">Tous les types</option>
                    <?php foreach ($typesBois as $type): ?>
                        <option
                            value="<?= (int) $type['id'] ?>"
                            <?= $idTypeBois === (int) $type['id'] ? 'selected' : '' ?>
                        >
                            <?= $echapper($type['libelle']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        </form>

        <p id="etat-recherche" class="etat-recherche" role="status" aria-live="polite"></p>

        <div id="resultats-catalogue" aria-live="polite">
            <?php if ($erreurCatalogue !== ''): ?>
                <p class="message message-erreur" role="alert"><?= $echapper($erreurCatalogue) ?></p>

            <?php elseif (!$produits): ?>
                <p class="catalogue-vide">Aucun produit ne correspond à votre recherche.</p>

            <?php else: ?>
                <p class="catalogue-compte">
                    <?= $nombreProduits ?> produit<?= $nombreProduits > 1 ? 's' : '' ?>
                </p>

                <section class="grille-produits" aria-label="Tous les produits">
                    <?php foreach ($produits as $produit): ?>
                        <article class="carte-produit">
                            <img
                                class="produit-image"
                                src="includes/images/produits/<?= $echapper(basename($produit['image'])) ?>"
                                alt="<?= $echapper($produit['libelle']) ?>"
                                loading="lazy"
                            >

                            <?php if ($produit['type_bois'] !== null): ?>
                                <p class="produit-categorie"><?= $echapper($produit['type_bois']) ?></p>
                            <?php endif; ?>

                            <h2><?= $echapper($produit['libelle']) ?></h2>

                            <?php if ($produit['description'] !== null && $produit['description'] !== ''): ?>
                                <p class="produit-description"><?= $echapper($produit['description']) ?></p>
                            <?php endif; ?>

                            <p class="produit-prix">
                                <?= number_format((float) $produit['prix'], 2, ',', ' ') ?>&nbsp;€
                            </p>

                            <?php if ($role === 'client'): ?>
                                <form method="post" action="index.php?uc=panier" class="formulaire-ajout-panier">
                                    <input type="hidden" name="action" value="ajouter">
                                    <input type="hidden" name="id_produit" value="<?= (int) $produit['id'] ?>">

                                    <input
                                        type="hidden"
                                        name="jeton_csrf"
                                        value="<?= $echapper($jetonCsrfPanier) ?>"
                                    >

                                    <button type="submit" class="bouton-inscription">Ajouter au panier</button>
                                </form>

                            <?php elseif ($role === ''): ?>
                                <p class="produit-connexion">
                                    <a href="index.php?uc=auth">Connectez-vous pour commander</a>
                                </p>
                            <?php endif; ?>
                        </article>

                    <?php endforeach; ?>
                </section>

                <?php if ($nombrePages > 1): ?>
                    <?php
                    $lienPage = static function (int $numeroPage) use ($recherche, $idTypeBois): string {
                        return 'index.php?' . http_build_query([
                            'uc' => 'produits',
                            'page' => $numeroPage,
                            'recherche' => $recherche,
                            'type_bois' => $idTypeBois,
                        ]);
                    };
                    ?>
                    <nav class="pagination-catalogue" aria-label="Pages du catalogue">
                        <?php if ($page > 1): ?>
                            <a href="<?= $echapper($lienPage($page - 1)) ?>">Précédent</a>
                        <?php endif; ?>

                        <?php for ($numeroPage = 1; $numeroPage <= $nombrePages; $numeroPage++): ?>
                            <?php if ($numeroPage === $page): ?>
                                <span aria-current="page"><?= $numeroPage ?></span>
                            <?php else: ?>
                                <a href="<?= $echapper($lienPage($numeroPage)) ?>">
                                    <?= $numeroPage ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $nombrePages): ?>
                            <a href="<?= $echapper($lienPage($page + 1)) ?>">Suivant</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    <script>
        const formulaireFiltres = document.querySelector('#filtres-catalogue');
        const champRecherche = document.querySelector('#recherche-produit');
        const filtreType = document.querySelector('#type-bois');
        const resultatsCatalogue = document.querySelector('#resultats-catalogue');
        const etatRecherche = document.querySelector('#etat-recherche');
        let minuteurRecherche;
        let requeteEnCours = null;

        async function actualiserCatalogue(url, ajouterHistorique) {
            if (requeteEnCours) {
                requeteEnCours.abort();
            }

            const controleurRequete = new AbortController();
            requeteEnCours = controleurRequete;
            resultatsCatalogue.setAttribute('aria-busy', 'true');
            etatRecherche.textContent = 'Recherche des produits…';

            try {
                const reponse = await fetch(url, { signal: controleurRequete.signal });
                const pageTexte = await reponse.text();
                const documentReponse = new DOMParser().parseFromString(pageTexte, 'text/html');
                const nouveauxResultats = documentReponse.querySelector('#resultats-catalogue');

                if (!reponse.ok || !nouveauxResultats) {
                    throw new Error('Impossible de charger les produits.');
                }

                resultatsCatalogue.innerHTML = nouveauxResultats.innerHTML;

                if (ajouterHistorique) {
                    history.pushState({}, '', url);
                }

                etatRecherche.textContent = '';
            } catch (erreur) {
                if (erreur.name !== 'AbortError') {
                    etatRecherche.textContent = 'La recherche est momentanément indisponible.';
                }
            } finally {
                if (requeteEnCours === controleurRequete) {
                    requeteEnCours = null;
                    resultatsCatalogue.removeAttribute('aria-busy');
                }
            }
        }

        formulaireFiltres.addEventListener('submit', (evenement) => {
            evenement.preventDefault();
            const url = new URL(formulaireFiltres.action, window.location.href);
            url.search = new URLSearchParams(new FormData(formulaireFiltres));
            actualiserCatalogue(url, true);
        });

        champRecherche.addEventListener('input', () => {
            clearTimeout(minuteurRecherche);
            minuteurRecherche = setTimeout(() => formulaireFiltres.requestSubmit(), 300);
        });

        filtreType.addEventListener('change', () => formulaireFiltres.requestSubmit());

        resultatsCatalogue.addEventListener('click', (evenement) => {
            const lien = evenement.target.closest('.pagination-catalogue a');
            if (lien) {
                evenement.preventDefault();
                actualiserCatalogue(lien.href, true);
            }
        });

        window.addEventListener('popstate', () => {
            const url = new URL(window.location.href);
            champRecherche.value = url.searchParams.get('recherche') || '';
            filtreType.value = url.searchParams.get('type_bois') || '0';
            actualiserCatalogue(url, false);
        });
    </script>
</body>
</html>
