<?php
/**
 * Encerramento de Sessão (Logout)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

fazerLogout();
header('Location: ' . BASE_URL . '/index.php');
exit;
