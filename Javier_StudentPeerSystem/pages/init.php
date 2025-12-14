<?php
// pages/init.php

// Define the root path relative to this file
$rootPath = dirname(__DIR__);

// Load environment variables
require_once $rootPath . '/classes/envLoader.php';
EnvLoader::load($rootPath . '/.env');

// Secure session configuration
if (session_status() === PHP_SESSION_NONE) {
    // Determine if we are on HTTPS (basic check, useful for Hostinger)
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'cookie_secure' => $isHttps, // Auto-enable secure cookies if on HTTPS
        'use_strict_mode' => true
    ]);
}
?>