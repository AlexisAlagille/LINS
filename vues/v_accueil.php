<?php
$produits = $produits ?? [];
$erreurCatalogue = $erreurCatalogue ?? '';
$echapper = static function (string $valeur): string {
	return htmlspecialchars($valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Découvrez LINS et sa sélection de produits en bois.">
	<title>LINS | Produits en bois</title>
	<link rel="stylesheet" href="includes/css/style.css">
</head>
<body class="page-accueil">
	<?php require __DIR__ . '/v_header.php'; ?>
	<main>
		<section class="accueil-hero" aria-labelledby="titre-accueil">
			<div class="accueil-hero-contenu">
				<p class="sur-titre">LINS · PRESBOIS</p>
				<h1 id="titre-accueil">Le bois, simplement bien pensé.</h1>
				<p class="accueil-hero-texte">
					LINS met à l’honneur le bois à travers une sélection de produits
					conçus pour accompagner vos projets et votre quotidien.
				</p>
				<a class="accueil-bouton" href="#catalogue">Découvrir les produits</a>
			</div>
			<div class="accueil-hero-repere" aria-hidden="true">
				<span>MATIÈRE</span>
				<span>USAGE</span>
				<span>DURABILITÉ</span>
			</div>
		</section>

		<section class="accueil-entreprise" id="entreprise" aria-labelledby="titre-entreprise">
			<p class="sur-titre">Notre entreprise</p>
			<h2 id="titre-entreprise">Une attention particulière portée à la matière.</h2>
			<p>
				Chez LINS, nous valorisons le bois et ses possibilités. Notre catalogue
				rassemble des produits aux usages variés, présentés avec leurs
				caractéristiques pour vous aider à choisir en toute simplicité.
			</p>
		</section>

		<section class="accueil-catalogue" id="catalogue" aria-labelledby="titre-catalogue">
			<div class="accueil-section-entete">
				<div>
					<p class="sur-titre">À découvrir</p>
					<h2 id="titre-catalogue">Nos produits mis en avant</h2>
				</div>
				<p>Une sélection choisie par notre équipe.</p>
			</div>

			<?php if ($erreurCatalogue !== ''): ?>
				<p class="accueil-etat" role="status"><?= $echapper($erreurCatalogue) ?></p>
			<?php elseif (!$produits): ?>
				<p class="accueil-etat">Aucun produit n’est mis en avant pour le moment.</p>
			<?php else: ?>
				<div class="produits-grille">
					<?php foreach ($produits as $produit): ?>
						<article class="produit-item">
							<img
								class="produit-image-accueil"
								src="includes/images/produits/<?= $echapper(basename((string) $produit['image'])) ?>"
								alt=""
								loading="lazy"
							>
							<h3><?= $echapper((string) $produit['libelle']) ?></h3>
							<?php if (!empty($produit['description'])): ?>
								<p class="produit-description">
									<?= $echapper((string) $produit['description']) ?>
								</p>
							<?php endif; ?>
							<p class="produit-prix">
								<?= number_format((float) $produit['prix'], 2, ',', ' ') ?> €
							</p>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
	</main>

	<footer class="accueil-pied">
		<a class="accueil-marque" href="index.php?uc=accueil">LINS</a>
		<p>© <?= date('Y') ?> LINS · PRESBOIS</p>
	</footer>
</body>
</html>
