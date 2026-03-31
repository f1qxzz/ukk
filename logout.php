<?php
require_once 'includes/session.php';
initSession();

// hancurin semua session
session_unset();
session_destroy();

// balik ke index
header("Location: index.php");
exit;