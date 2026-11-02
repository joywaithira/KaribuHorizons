<?php
session_start(); session_unset(); session_destroy();
$adminPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
header('Location: ' . $adminPath . '/login.php'); exit;
