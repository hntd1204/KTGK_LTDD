<?php
/**
 * Admin Logout Action
 */
require_once __DIR__ . '/helpers/auth.php';
Auth::logout();
header('Location: login.php');
exit;
