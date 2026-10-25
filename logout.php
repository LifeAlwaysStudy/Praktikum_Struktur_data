<?php
require __DIR__ . '/includes/init.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_check();
$_SESSION = [];
session_destroy();
header('Location: login.php');
