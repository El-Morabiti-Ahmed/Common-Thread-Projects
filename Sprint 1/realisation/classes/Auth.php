<?php
/**
 * Login state, roles and CSRF protection.
 * The role is read from the database on every request, so a change
 * made in MySQL Workbench works immediately.
 */
class Auth
{
    private static ?array $currentUser = null;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
            session_start();
        }
    }

    public static function login(array $player): void
    {
        session_regenerate_id(true);          // stops session fixation
        $_SESSION['player_id'] = (int) $player['id'];
        self::$currentUser = null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        self::$currentUser = null;
    }

    /** The logged-in player (array) or null. */
    public static function user(): ?array
    {
        if (!isset($_SESSION['player_id'])) {
            return null;
        }

        if (self::$currentUser === null) {
            $player = (new Player())->findById((int) $_SESSION['player_id']);
            if ($player === null) {           // account was deleted
                unset($_SESSION['player_id']);
                return null;
            }
            self::$currentUser = $player;
        }

        return self::$currentUser;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && $user['role'] === 'admin';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Helper::flash('error', 'Please log in to continue.');
            Helper::redirect('login.php');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            Helper::flash('error', 'You need an admin account to open that page.');
            Helper::redirect('index.php');
        }
    }

    // ---- CSRF: a secret token hidden in every form ---------------
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return !empty($_SESSION['csrf']) && is_string($token)
            && hash_equals($_SESSION['csrf'], $token);
    }
}
