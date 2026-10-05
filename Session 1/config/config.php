<?php
/**
 * Global settings.
 * Change the DB_* values so they match your MySQL server.
 */

// ---- Database ------------------------------------------------
const DB_HOST = 'localhost';
const DB_NAME = 'boardgame_hub';
const DB_USER = 'root';
const DB_PASS = 'ROOTisnotbad12@';          // XAMPP/WAMP: empty. MAMP: 'root'

// ---- App -----------------------------------------------------
const APP_NAME = 'Board Game Hub';
const DEBUG    = true;       // set to false when the site is "live"

if (DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// ---- BASE_URL --------------------------------------------------
// The web path of the project folder (e.g. "/boardgame-hub").
// It is detected automatically. If your CSS does not load,
// comment the block below and write it by hand instead:
//     define('BASE_URL', '/boardgame-hub');
$projectPath = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
$docRoot     = $_SERVER['DOCUMENT_ROOT'] ?? '';
$docRoot     = $docRoot !== '' ? str_replace('\\', '/', (string) realpath($docRoot)) : '';

if ($docRoot !== '' && strpos($projectPath, $docRoot) === 0) {
    define('BASE_URL', rtrim(substr($projectPath, strlen($docRoot)), '/'));
} else {
    define('BASE_URL', '');
}
unset($projectPath, $docRoot);
