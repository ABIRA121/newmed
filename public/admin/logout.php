<?php
// Include configuration and autoloader
require_once '../config/config.php';
require_once '../app/autoload.php';

$session = new Session();
$auth = new Auth();

$auth->logout();

header('Location: login.php');
exit;
?>