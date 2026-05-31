<?php
// logout.php - User Logout

session_start();
session_destroy();
header("Location: /aromea/index.php");
exit;
?>
