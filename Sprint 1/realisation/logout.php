<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Logout is a POST form with a CSRF token (see the menu in header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf($_POST['csrf'] ?? null)) {
    Auth::logout();
    Auth::start();                       // new empty session for the flash message
    Helper::flash('success', 'You are logged out.');
}

Helper::redirect('index.php');
