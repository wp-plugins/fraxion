<?php
// Version: 0.4.4
include("fraxion_class.php");
$fraxjax = new FraxionPayments();
$message = "";
$action_method = $_POST['action'];
eval("\$message = \$fraxjax->" . $action_method . "(\$_POST['siteID'],\$_POST['postID'],\$_POST['userID'],\$_POST['permit']);");
die($message);
?>