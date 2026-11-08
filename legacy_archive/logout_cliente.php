<?php
session_start();
unset($_SESSION["cliente_logado"]);
unset($_SESSION["cliente_id"]);
unset($_SESSION["cliente_user"]);
unset($_SESSION["cliente_email"]);
session_destroy();
header("Location: login.php");
exit;
