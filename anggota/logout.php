<?php
require_once '../includes/session.php';
logout();
header('Location: ../login.php');
exit;
