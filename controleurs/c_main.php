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
    case 'monCompte' : { include "c_monCompte.php" ; break ;}
    case 'adminUtilisateurs' : { include "c_adminUtilisateur.php" ; break ;}
}

?>
