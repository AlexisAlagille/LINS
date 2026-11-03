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
}

?>


