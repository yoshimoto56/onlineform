<?php
session_start();

include("common.inc");

if(!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit;
}

session_destroy();
unset($_SESSION['user']);
header("Location: index.php");
exit;

?>