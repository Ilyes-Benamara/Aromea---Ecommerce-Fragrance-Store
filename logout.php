<?php
session_start();
session_destroy();
header("Location: /aromea/index.php");
exit;
?>
