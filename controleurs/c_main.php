<?php
if (!isset($_REQUEST['uc'])) {
    $uc = "auth" ;
}
else {
    $uc = $_REQUEST['uc'] ;
}

switch ($uc)
{
    case 'auth' : {  include "c_auth.php" ; break ;}
    case 'accueil' : { include "c_accueil.php" ; break ;}
    case 'produits' : { include "c_produits.php" ; break ;}
    case 'panier' : { include "c_panier.php" ; break ;}
    case 'commandes' : { include "c_commandes.php" ; break ;}
    case 'monCompte' : { include "c_monCompte.php" ; break ;}
    case 'adminUtilisateurs' : { include "c_adminUtilisateur.php" ; break ;}
    case 'gestion_produits' : { include "c_gestion_produits.php" ; break ;}
    case 'gestion_categories' : { include "c_gestion_categories.php" ; break ;}
}

?>
