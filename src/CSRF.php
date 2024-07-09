<?php
namespace Opensitez\Simplicity;

class CSRF {
    protected static $token_name = 'csrf_token';

    public static function generate_token() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate a new token only if it hasn't been generated yet for this session
        if (!isset($_SESSION[self::$token_name])) {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::$token_name] = $token;
        }

        return $_SESSION[self::$token_name];
    }
    public static function unset_token() {
        unset($_SESSION[self::$token_name]);
    }
    public static function get_token() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION[self::$token_name])) {
            return $_SESSION[self::$token_name];
        } else {
            return null;
        }
    }
    public static function reset_token() {
        self::unset_token();
        self::generate_token();
        return self::get_token();
    }
    public static function validate_token($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION[self::$token_name]) && $_SESSION[self::$token_name] === $token) {
            return true;
        } else {
            return false;
        }
    }
}

