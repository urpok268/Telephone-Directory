<?php
require __DIR__ . '/app/auth.php';
session_destroy();
header('Location: index.php');
exit;
