<?php
$menusParRole = [
    'client' => [
        ['Accueil', 'index.php?uc=accueil'],
        ['Nos produits', 'index.php?uc=produits'],
        ['Mes commandes', 'index.php?uc=commandes'],
    ],
    'admin' => [
        ['Tableau de bord', 'index.php?uc=accueil'],
        ['Produits', 'index.php?uc=produits'],
        ['Commandes', 'index.php?uc=commandes'],
        ['Utilisateurs', 'index.php?uc=utilisateurs'],
    ],
];

$roleConnecte = $_SESSION['role'] ?? '';

if (!is_string($roleConnecte) || !isset($menusParRole[$roleConnecte])) {
    return;
}

if (empty($_SESSION['jeton_csrf_deconnexion'])) {
    $_SESSION['jeton_csrf_deconnexion'] = bin2hex(random_bytes(32));
}
?>
<header class="navigation-principale">
    <div class="navigation-interieur">
        <a class="navigation-marque" href="index.php?uc=accueil" aria-label="LINS, accueil">
            <img class="navigation-logo" src="includes/images/logo-lins.png" alt="">
            <span class="navigation-identite">
                <strong>LINS</strong>
                <small>Le bois, bien choisi.</small>
            </span>
        </a>
        <nav class="navigation-liens" aria-label="Navigation principale">
            <?php foreach ($menusParRole[$roleConnecte] as [$libelle, $destination]): ?>
                <?php
                $parametresLien = [];
                parse_str((string) parse_url($destination, PHP_URL_QUERY), $parametresLien);
                $pageActive = isset($_GET['uc'], $parametresLien['uc'])
                    && $_GET['uc'] === $parametresLien['uc'];
                ?>
                <a
                    href="<?= htmlspecialchars($destination, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    <?= $pageActive ? 'aria-current="page"' : '' ?>
                >
                    <?= htmlspecialchars($libelle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <form method="post" action="index.php?uc=auth&amp;action=logout" class="formulaire-deconnexion">
            <input
                type="hidden"
                name="jeton_csrf"
                value="<?= htmlspecialchars($_SESSION['jeton_csrf_deconnexion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
            >
            <button type="submit">Déconnexion</button>
        </form>
    </div>
</header>
