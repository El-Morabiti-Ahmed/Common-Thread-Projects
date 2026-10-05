<?php
/**
 * Included at the top of every page:
 * loads the config, the class autoloader and starts the session.
 */
require_once __DIR__ . '/../config/config.php';

// Loads classes/ClassName.php automatically when a class is used
spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

Auth::start();

// Short helper functions for the HTML templates
function e(?string $text): string
{
    return Helper::e($text);
}

function url(string $path = ''): string
{
    return Helper::url($path);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(Auth::csrfToken()) . '">';
}
