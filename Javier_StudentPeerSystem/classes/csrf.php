<?php
class CSRF
{
    public static function generateToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateToken($token)
    {
        if (!isset($_SESSION['csrf_token']) || !isset($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}