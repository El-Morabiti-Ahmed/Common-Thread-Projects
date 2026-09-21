<?php
/**
 * Small tools used everywhere: escaping, links, redirects, flash messages.
 */
class Helper
{
    /** Escape text before printing it in HTML (protects against XSS). */
    public static function e(?string $text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }

    /** Build a link relative to the project folder. */
    public static function url(string $path = ''): string
    {
        return BASE_URL . '/' . ltrim($path, '/');
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . self::url($path));
        exit;
    }

    /** Returns "active" when $path is the page being displayed (for the menu). */
    public static function activeClass(string $path): string
    {
        return ($_SERVER['SCRIPT_NAME'] ?? '') === self::url($path) ? 'active' : '';
    }

    // ---- flash messages: shown once, on the next page -----------
    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function pullFlash(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }
}
